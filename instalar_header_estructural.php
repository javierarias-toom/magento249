<?php

use Magento\Framework\App\Bootstrap;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;

require __DIR__ . '/app/bootstrap.php';

$bootstrap = Bootstrap::create(BP, $_SERVER);
$objectManager = $bootstrap->getObjectManager();

try {
    $objectManager
        ->get(\Magento\Framework\App\State::class)
        ->setAreaCode('adminhtml');
} catch (\Exception $e) {
}

$template = __DIR__ .
    '/app/design/frontend/Smartwave/porto/' .
    'Smartwave_Porto/templates/html/header.phtml';

$backup = $template . '.forbesons-original';

/*
 * Crear una sola copia de seguridad.
 */
if (!file_exists($backup)) {
    if (!copy($template, $backup)) {
        exit("ERROR: No se pudo respaldar header.phtml\n");
    }

    echo "Copia de seguridad creada.\n";
}

$content = file_get_contents($template);

if ($content === false) {
    exit("ERROR: No se pudo leer header.phtml\n");
}

/*
 * Trabajar solamente dentro del header tipo 1.
 */
$startMarker = '<?php if($header_type == 1): ?>';
$endMarker = '<?php elseif($header_type == 2): ?>';

$start = strpos($content, $startMarker);
$end = strpos($content, $endMarker);

if ($start === false || $end === false || $end <= $start) {
    exit("ERROR: No se encontró la sección del header tipo 1.\n");
}

$type1 = substr($content, $start, $end - $start);

if (
    strpos(
        $type1,
        'FORBESONS_NAV_INSIDE_HEADER'
    ) === false
) {
    /*
     * Quitar el menú que Porto imprime debajo del header.
     */
    $navigation = '<?php echo $this->getChildHtml("navigation.sections"); ?>';

    $navPosition = strrpos($type1, $navigation);

    if ($navPosition === false) {
        exit("ERROR: No se encontró navigation.sections.\n");
    }

    $type1 = substr_replace(
        $type1,
        '',
        $navPosition,
        strlen($navigation)
    );

    /*
     * Insertar el menú justo después del logo.
     */
    $logo = '<?php echo $this->getChildHtml("logo"); ?>';

    $replacement = $logo . PHP_EOL .
        '        <?php /* FORBESONS_NAV_INSIDE_HEADER */ ?>' .
        PHP_EOL .
        '        <?php echo $this->getChildHtml("navigation.sections"); ?>';

    $logoPosition = strpos($type1, $logo);

    if ($logoPosition === false) {
        exit("ERROR: No se encontró el logo del header tipo 1.\n");
    }

    $type1 = substr_replace(
        $type1,
        $replacement,
        $logoPosition,
        strlen($logo)
    );

    $content = substr($content, 0, $start) .
        $type1 .
        substr($content, $end);

    if (file_put_contents($template, $content) === false) {
        exit("ERROR: No se pudo modificar header.phtml\n");
    }

    echo "Menú movido junto al logo.\n";
} else {
    echo "El menú ya estaba colocado correctamente.\n";
}

/*
 * Eliminar los bloques importados que producen:
 * - Call us now
 * - redes sociales superiores
 */
$connection = $objectManager
    ->get(ResourceConnection::class)
    ->getConnection();

$table = $connection->getTableName('core_config_data');

$connection->delete(
    $table,
    [
        'path IN (?)' => [
            'porto_settings/header/static_block',
            'porto_settings/header/static_block_top',
        ],
    ]
);

$config = $objectManager->get(ScopeConfigInterface::class);
$writer = $objectManager->get(WriterInterface::class);

$current = (string) $config->getValue(
    'design/head/includes',
    \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
    0
);

/*
 * Eliminar una instalación anterior de este mismo CSS.
 */
$current = preg_replace(
    '#\s*<style\s+id=["\']forbesons-structural-header["\']' .
    '.*?</style>\s*#is',
    PHP_EOL,
    $current
);

$css = <<<'CSS'
<style id="forbesons-structural-header">
/* FORBESONS STRUCTURAL HEADER START */

@media (min-width: 992px) {
    /* Barra superior */
    .page-header.type1 .main-panel-top {
        min-height: 40px !important;
        border-bottom: 1px solid #eee !important;
    }

    .page-header.type1 .main-panel-top .container,
    .page-header.type1 .main-panel-top .main-panel-inner {
        width: 100% !important;
        max-width: none !important;
    }

    .page-header.type1 .main-panel-top .panel.wrapper {
        display: flex !important;
        align-items: center !important;
        justify-content: space-between !important;
        width: 88% !important;
        max-width: 1690px !important;
        min-height: 40px !important;
        margin: 0 auto !important;
        padding: 0 !important;
    }

    .page-header.type1 .main-panel-top .top-links-icon {
        display: none !important;
    }

    .page-header.type1 .main-panel-top .header-right,
    .page-header.type1 .main-panel-top .panel.header,
    .page-header.type1 .main-panel-top .header.links {
        display: flex !important;
        align-items: center !important;
        width: auto !important;
        height: auto !important;
        margin: 0 !important;
        padding: 0 !important;
    }

    .page-header.type1 .main-panel-top .header.links > li {
        display: block !important;
        margin: 0 0 0 22px !important;
        padding: 0 !important;
        white-space: nowrap !important;
    }

    /* Fila principal */
    .page-header.type1 .header-main {
        min-height: 105px !important;
        background: #fff !important;
    }

    .page-header.type1 .header-main .header.content.header-row {
        display: flex !important;
        align-items: center !important;
        justify-content: space-between !important;
        gap: 25px !important;
        width: 88% !important;
        max-width: 1690px !important;
        min-height: 105px !important;
        margin: 0 auto !important;
        padding: 0 !important;
        box-sizing: border-box !important;
    }

    /*
     * Columna izquierda:
     * logo + navegación, ahora realmente dentro del mismo contenedor.
     */
    .page-header.type1 .header-main .header-left {
        display: flex !important;
        flex: 0 0 auto !important;
        align-items: center !important;
        gap: 45px !important;
        width: auto !important;
        min-width: 600px !important;
        margin: 0 !important;
        padding: 0 !important;
    }

    .page-header.type1 .header-main .logo {
        position: static !important;
        display: block !important;
        flex: 0 0 180px !important;
        width: 180px !important;
        max-width: 180px !important;
        height: auto !important;
        margin: 0 !important;
        padding: 0 !important;
        transform: none !important;
        float: none !important;
    }

    .page-header.type1 .header-main .logo img {
        display: block !important;
        width: 180px !important;
        max-width: 180px !important;
        height: auto !important;
        max-height: 40px !important;
        margin: 0 !important;
    }

    /* Menú */
    .page-header.type1 .header-left .nav-sections {
        position: static !important;
        display: block !important;
        flex: 0 0 auto !important;
        width: auto !important;
        height: auto !important;
        margin: 0 !important;
        padding: 0 !important;
        transform: none !important;
        background: transparent !important;
        box-shadow: none !important;
    }

    .page-header.type1 .header-left
    .nav-sections-item-content,
    .page-header.type1 .header-left .navigation {
        display: block !important;
        width: auto !important;
        height: auto !important;
        margin: 0 !important;
        padding: 0 !important;
        background: transparent !important;
    }

    .page-header.type1 .header-left .navigation > ul {
        display: flex !important;
        align-items: center !important;
        gap: 30px !important;
        width: auto !important;
        margin: 0 !important;
        padding: 0 !important;
    }

    .page-header.type1 .header-left
    .navigation li.level0 {
        position: relative !important;
        display: block !important;
        width: auto !important;
        margin: 0 !important;
        padding: 0 !important;
    }

    .page-header.type1 .header-left
    .navigation li.level0 > .level-top {
        display: flex !important;
        align-items: center !important;
        height: 52px !important;
        padding: 0 !important;
        font-size: 13px !important;
        font-weight: 700 !important;
        line-height: 52px !important;
        white-space: nowrap !important;
    }

    /* Submenú Bicicletas */
    .page-header.type1 .navigation
    li.level0 > .submenu {
        top: 52px !important;
        left: 0 !important;
        width: 245px !important;
        min-width: 245px !important;
        padding: 12px 0 !important;
        background: #fff !important;
        box-shadow: 0 8px 28px rgba(0,0,0,.12) !important;
    }

    .page-header.type1 .navigation
    li.level0 > .submenu li {
        display: block !important;
        width: 100% !important;
    }

    .page-header.type1 .navigation
    li.level0 > .submenu a {
        display: block !important;
        width: 100% !important;
        padding: 10px 22px !important;
        line-height: 22px !important;
        white-space: normal !important;
        box-sizing: border-box !important;
    }

    /* Centro: buscador */
    .page-header.type1 .header-main .header-center {
        display: flex !important;
        flex: 1 1 auto !important;
        justify-content: flex-end !important;
        align-items: center !important;
        width: auto !important;
        min-width: 420px !important;
        margin: 0 0 0 auto !important;
        padding: 0 !important;
    }

    .page-header.type1 .header-center .search-area {
        display: block !important;
        width: 100% !important;
        max-width: 570px !important;
        margin: 0 !important;
    }

    .page-header.type1 .search-toggle-icon {
        display: none !important;
    }

    .page-header.type1 .block-search {
        position: relative !important;
        display: block !important;
        width: 100% !important;
        max-width: 570px !important;
        height: 50px !important;
        margin: 0 !important;
        padding: 0 !important;
        float: none !important;
    }

    .page-header.type1 .block-search .block-content,
    .page-header.type1 .block-search form,
    .page-header.type1 .block-search .field.search,
    .page-header.type1 .block-search .control {
        position: relative !important;
        display: block !important;
        width: 100% !important;
        height: 50px !important;
        margin: 0 !important;
        padding: 0 !important;
    }

    .page-header.type1 .block-search input {
        display: block !important;
        width: 100% !important;
        height: 50px !important;
        padding: 0 235px 0 22px !important;
        border: 1px solid #e1e1e1 !important;
        border-radius: 26px !important;
        background: #f7f7f7 !important;
        box-sizing: border-box !important;
    }

    .page-header.type1 .block-search .search-category {
        position: absolute !important;
        right: 52px !important;
        top: 0 !important;
        z-index: 3 !important;
        display: block !important;
        width: 180px !important;
        height: 50px !important;
        border-left: 1px solid #ddd !important;
        background: #f7f7f7 !important;
    }

    .page-header.type1
    .block-search .search-category select {
        display: block !important;
        width: 180px !important;
        min-width: 180px !important;
        height: 50px !important;
        padding: 0 28px 0 18px !important;
        border: 0 !important;
        background-color: transparent !important;
        font-size: 14px !important;
        box-sizing: border-box !important;
    }

    .page-header.type1 .block-search .actions {
        position: absolute !important;
        right: 0 !important;
        top: 0 !important;
        z-index: 4 !important;
        width: 52px !important;
        height: 50px !important;
    }

    .page-header.type1 .block-search .action.search {
        display: block !important;
        width: 52px !important;
        height: 50px !important;
        border: 0 !important;
        border-radius: 0 26px 26px 0 !important;
        background: #222529 !important;
        opacity: 1 !important;
    }

    /* Derecha: cuenta, favoritos y carrito */
    .page-header.type1 .header-main
    .header-row > .header-right {
        display: flex !important;
        flex: 0 0 auto !important;
        align-items: center !important;
        gap: 16px !important;
        width: auto !important;
        min-width: 145px !important;
        margin: 0 !important;
        padding: 0 !important;
    }

    .page-header.type1 .header-contact {
        display: flex !important;
        align-items: center !important;
        gap: 16px !important;
        margin: 0 !important;
    }

    .page-header.type1 .header-contact
    > :not(.my-account):not(.wishlist) {
        display: none !important;
    }

    .page-header.type1 .my-account,
    .page-header.type1 .wishlist {
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        width: 34px !important;
        height: 50px !important;
        margin: 0 !important;
        padding: 0 !important;
    }

    .page-header.type1 .minicart-wrapper {
        position: relative !important;
        display: block !important;
        width: 55px !important;
        height: 50px !important;
        margin: 0 !important;
        padding: 0 !important;
        transform: none !important;
        float: none !important;
    }

    .page-header.type1
    .minicart-wrapper .action.showcart {
        display: block !important;
        width: 55px !important;
        height: 50px !important;
    }

    .page-header.type1
    .minicart-wrapper .action.showcart .text,
    .page-header.type1
    .minicart-wrapper .action.showcart .subtotal,
    .page-header.type1
    .minicart-wrapper .action.showcart .cart-title {
        display: none !important;
    }
}

/* Ajuste para portátil */
@media (min-width: 992px) and (max-width: 1500px) {
    .page-header.type1 .header-main
    .header.content.header-row {
        width: 96% !important;
        gap: 18px !important;
    }

    .page-header.type1 .header-main .header-left {
        gap: 25px !important;
        min-width: 520px !important;
    }

    .page-header.type1 .header-left
    .navigation > ul {
        gap: 18px !important;
    }

    .page-header.type1 .header-main
    .header-center {
        min-width: 380px !important;
    }

    .page-header.type1 .header-center
    .search-area,
    .page-header.type1 .block-search {
        max-width: 480px !important;
    }
}

/* FORBESONS STRUCTURAL HEADER END */
</style>
CSS;

$current = trim($current) . PHP_EOL . $css;

$writer->save(
    'design/head/includes',
    $current,
    'default',
    0
);

echo "Bloques antiguos de llamada y redes eliminados.\n";
echo "CSS estructural instalado.\n";
echo "Instalación terminada.\n";
