<?php use App\Core\View; ob_start() ?>
<?php View::partial('site/requests/_detail', get_defined_vars()) ?>
<?php View::partial('site/account/_shell', ['user' => $user, 'inner' => ob_get_clean()]) ?>
