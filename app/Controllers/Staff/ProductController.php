<?php
declare(strict_types=1);

namespace App\Controllers\Staff;

use App\Core\Auth;
use App\Core\Db;
use App\Core\Validator;
use App\Core\View;
use App\Services\Tebex;
use RuntimeException;

final class ProductController
{
    public function index(): void
    {
        Auth::require();
        View::render('staff/products/index', [
            'products'   => Db::all('SELECT * FROM products ORDER BY active DESC, featured DESC, sort, title'),
            'configured' => Tebex::configured(),
        ], 'Scripts');
    }

    public function create(): void
    {
        Auth::require();
        View::render('staff/products/form', ['product' => null], 'Neues Script');
    }

    public function edit(array $p): void
    {
        Auth::require();
        View::render('staff/products/form', ['product' => $this->find($p)], 'Script bearbeiten');
    }

    public function store(): void
    {
        $staff = Auth::require();
        $data = $this->validate('/scripts/neu');
        $id = Db::insert(
            'INSERT INTO products (title, category, summary, description, price_label, image_url, tebex_url, frameworks, active, featured, sort)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            array_values($data)
        );
        audit('staff:' . $staff['id'], 'product_created', '#' . $id);
        flash('success', 'Script angelegt.');
        redirect('/scripts');
    }

    public function update(array $p): void
    {
        $staff = Auth::require();
        $product = $this->find($p);
        $data = $this->validate('/scripts/' . $product['id']);
        Db::exec(
            "UPDATE products SET title = ?, category = ?, summary = ?, description = ?, price_label = ?, image_url = ?, tebex_url = ?,
             frameworks = ?, active = ?, featured = ?, sort = ?, updated_at = datetime('now') WHERE id = ?",
            [...array_values($data), $product['id']]
        );
        audit('staff:' . $staff['id'], 'product_updated', '#' . $product['id']);
        flash('success', 'Gespeichert.' . ($product['tebex_id'] ? ' Hinweis: Titel, Text, Preis und Bild werden beim nächsten Tebex-Sync wieder übernommen.' : ''));
        redirect('/scripts');
    }

    public function toggle(array $p): void
    {
        $staff = Auth::require();
        $product = $this->find($p);
        Db::exec("UPDATE products SET active = 1 - active, updated_at = datetime('now') WHERE id = ?", [$product['id']]);
        audit('staff:' . $staff['id'], 'product_toggled', '#' . $product['id']);
        redirect('/scripts');
    }

    public function destroy(array $p): void
    {
        $staff = Auth::requireAdmin();
        $product = $this->find($p);
        Db::exec('DELETE FROM products WHERE id = ?', [$product['id']]);
        audit('staff:' . $staff['id'], 'product_deleted', '#' . $product['id'] . ' ' . $product['title']);
        flash('info', 'Script gelöscht.' . ($product['tebex_id'] ? ' Beim nächsten Sync kommt es zurück – zum Ausblenden lieber deaktivieren.' : ''));
        redirect('/scripts');
    }

    public function sync(): void
    {
        $staff = Auth::require();
        try {
            $r = Tebex::sync();
        } catch (RuntimeException $e) {
            error_log('[tebex] ' . $e->getMessage());
            fail_back([], '/scripts', 'Tebex-Sync fehlgeschlagen: ' . ($e->getMessage() === 'Tebex webstore token missing' ? 'kein Webstore-Identifier in der Config.' : 'Tebex hat nicht wie erwartet geantwortet. Stimmt der Identifier?'));
        }
        audit('staff:' . $staff['id'], 'tebex_sync', json_encode($r));
        flash('success', "Tebex-Sync: {$r['created']} neu, {$r['updated']} aktualisiert, {$r['hidden']} ausgeblendet.");
        redirect('/scripts');
    }

    private function validate(string $back): array
    {
        $v = new Validator($_POST);
        $title = $v->text('title', 2, 120, 'Titel');
        $category = $v->text('category', 0, 60, 'Kategorie');
        $summary = $v->text('summary', 0, 200, 'Kurzbeschreibung');
        $description = $v->text('description', 0, 3000, 'Beschreibung', true);
        $price = $v->text('price_label', 0, 40, 'Preis');

        $image = trim((string)($_POST['image_url'] ?? ''));
        if ($image !== '' && Tebex::safeImage($image) === '') {
            $v->addError('image_url', 'Bild-URL muss https sein und von einem erlaubten Host kommen (' . implode(', ', Tebex::imageHosts()) . ').');
        }
        $store = trim((string)($_POST['tebex_url'] ?? ''));
        if ($store !== '' && Tebex::safeStoreUrl($store) === '') {
            $v->addError('tebex_url', 'Bitte einen https-Link zu deinem Tebex-Shop angeben.');
        }
        $frameworks = array_values(array_intersect(Tebex::FRAMEWORKS, (array)($_POST['frameworks'] ?? [])));
        $sort = (int)($_POST['sort'] ?? 0);

        if ($v->fails()) {
            fail_back($v->errors(), $back);
        }
        return [
            'title' => $title, 'category' => $category, 'summary' => $summary, 'description' => $description,
            'price_label' => $price, 'image_url' => $image, 'tebex_url' => $store, 'frameworks' => implode(',', $frameworks),
            'active' => ($_POST['active'] ?? '') === '1' ? 1 : 0, 'featured' => ($_POST['featured'] ?? '') === '1' ? 1 : 0,
            'sort' => max(-999, min(999, $sort)),
        ];
    }

    private function find(array $p): array
    {
        return Db::one('SELECT * FROM products WHERE id = ?', [id_param($p)]) ?? abort(404);
    }
}
