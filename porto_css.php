<?php
use Magento\Framework\App\Bootstrap;
require __DIR__ . '/app/bootstrap.php';
$bootstrap = Bootstrap::create(BP, $_SERVER);
$obj = $bootstrap->getObjectManager();
$state = $obj->get('Magento\Framework\App\State');
$state->setAreaCode('adminhtml');

try {
    echo "Regenerando estructura visual...\n";
    $cssGenerator = $obj->get('\Smartwave\Porto\Model\Cssconfig\Generator');
    $cssGenerator->generateCss('design', 0, 0);
    $cssGenerator->generateCss('settings', 0, 0);
    echo "¡Estructura actualizada!\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
