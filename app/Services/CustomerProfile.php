<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Validator;

/** Customer master data shared by registration and the profile settings. */
final class CustomerProfile
{
    public const SALUTATIONS = ['herr' => 'Herr', 'frau' => 'Frau', 'divers' => 'Divers', 'keine' => 'Keine Angabe'];

    public const COUNTRIES = [
        'Deutschland', 'Österreich', 'Schweiz', 'Liechtenstein', 'Luxemburg', 'Belgien', 'Niederlande', 'Frankreich',
        'Italien', 'Spanien', 'Portugal', 'Polen', 'Tschechien', 'Dänemark', 'Schweden', 'Norwegen', 'Finnland',
        'Irland', 'Vereinigtes Königreich', 'Ungarn', 'Slowakei', 'Slowenien', 'Kroatien', 'Rumänien', 'Bulgarien',
        'Griechenland', 'Estland', 'Lettland', 'Litauen', 'Malta', 'Zypern', 'Türkei', 'USA', 'Kanada',
    ];

    private const ZIP = [
        'Deutschland' => ['/^\d{5}$/', '5 Ziffern'],
        'Österreich' => ['/^\d{4}$/', '4 Ziffern'],
        'Schweiz' => ['/^\d{4}$/', '4 Ziffern'],
        'Liechtenstein' => ['/^\d{4}$/', '4 Ziffern'],
        'Luxemburg' => ['/^(L-?)?\d{4}$/i', '4 Ziffern'],
        'Belgien' => ['/^\d{4}$/', '4 Ziffern'],
        'Dänemark' => ['/^\d{4}$/', '4 Ziffern'],
        'Niederlande' => ['/^\d{4} ?[A-Z]{2}$/i', 'z. B. 1234 AB'],
        'Frankreich' => ['/^\d{5}$/', '5 Ziffern'],
        'Italien' => ['/^\d{5}$/', '5 Ziffern'],
        'Spanien' => ['/^\d{5}$/', '5 Ziffern'],
        'Polen' => ['/^\d{2}-\d{3}$/', 'z. B. 00-950'],
    ];

    public static function addressedName(array $customer): string
    {
        $s = in_array($customer['salutation'] ?? '', ['herr', 'frau'], true) ? self::SALUTATIONS[$customer['salutation']] . ' ' : '';
        return $s . $customer['name'];
    }

    /** Street line as printed on documents. */
    public static function streetLine(array $customer): string
    {
        return trim($customer['street'] . ' ' . ($customer['house_number'] ?? ''));
    }

    public static function complete(array $customer): bool
    {
        return $customer['street'] !== '' && $customer['zip'] !== '' && $customer['city'] !== '';
    }

    /** Validates the profile fields of the request; returns the column values. */
    public static function validate(Validator $v, array $input): array
    {
        $d = [
            'salutation'    => $v->oneOf('salutation', array_keys(self::SALUTATIONS), 'Bitte wähle eine Anrede.'),
            'first_name'    => $v->text('first_name', 1, 60, 'Vorname'),
            'last_name'     => $v->text('last_name', 1, 60, 'Nachname'),
            'phone'         => $v->text('phone', 0, 30, 'Telefonnummer'),
            'company'       => '',
            'vat_id'        => '',
            'street'        => $v->text('street', 2, 100, 'Straße'),
            'house_number'  => $v->text('house_number', 1, 12, 'Hausnummer'),
            'address_extra' => $v->text('address_extra', 0, 80, 'Zusatz'),
            'zip'           => strtoupper($v->text('zip', 1, 10, 'PLZ')),
            'city'          => $v->text('city', 1, 80, 'Stadt'),
            'country'       => $v->oneOf('country', self::COUNTRIES, 'Bitte wähle ein Land.'),
        ];

        if ($d['phone'] !== '' && (!preg_match('/^\+?[0-9 ()\/\-]{6,30}$/', $d['phone']) || preg_match_all('/\d/', $d['phone']) < 6)) {
            $v->addError('phone', 'Bitte gib eine gültige Telefonnummer an.');
        }
        if ($d['house_number'] !== '' && !preg_match('/^\d+[a-zA-Z]?(\s?[-\/]\s?\d*[a-zA-Z]?)?$/u', $d['house_number'])) {
            $v->addError('house_number', 'Ungültige Hausnummer.');
        }
        if ($d['zip'] !== '' && $d['country'] !== '') {
            [$pattern, $hint] = self::ZIP[$d['country']] ?? ['/^[A-Z0-9][A-Z0-9 \-]{1,9}$/', 'Buchstaben und Ziffern'];
            if (!preg_match($pattern, $d['zip'])) {
                $v->addError('zip', "Ungültige PLZ ({$hint}).");
            }
        }

        if (($input['business'] ?? '') === '1') {
            $d['company'] = $v->text('company', 2, 120, 'Firmenname');
            $vat = strtoupper((string)preg_replace('/[\s.\-]/', '', $v->text('vat_id', 0, 20, 'USt-IdNr.')));
            if ($vat !== '' && !preg_match('/^[A-Z]{2}[A-Z0-9+*]{2,13}$/', $vat)) {
                $v->addError('vat_id', 'Ungültige USt-IdNr. (z. B. DE123456789).');
            }
            $d['vat_id'] = $vat;
        }

        $d['name'] = trim($d['first_name'] . ' ' . $d['last_name']);
        return $d;
    }
}
