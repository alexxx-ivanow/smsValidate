<?php
/** @var xPDOTransport $transport */
/** @var array $options */
/** @var modX $modx */
if ($transport->xpdo) {
    $modx =& $transport->xpdo;

    $dev = MODX_BASE_PATH . 'Extras/smsvalidate/';
    /** @var xPDOCacheManager $cache */
    $cache = $modx->getCacheManager();
    if (file_exists($dev) && $cache) {
        if (!is_link($dev . 'assets/components/smsvalidate')) {
            $cache->deleteTree(
                $dev . 'assets/components/smsvalidate/',
                ['deleteTop' => true, 'skipDirs' => false, 'extensions' => []]
            );
            symlink(MODX_ASSETS_PATH . 'components/smsvalidate/', $dev . 'assets/components/smsvalidate');
        }
        if (!is_link($dev . 'core/components/smsvalidate')) {
            $cache->deleteTree(
                $dev . 'core/components/smsvalidate/',
                ['deleteTop' => true, 'skipDirs' => false, 'extensions' => []]
            );
            symlink(MODX_CORE_PATH . 'components/smsvalidate/', $dev . 'core/components/smsvalidate');
        }
    }
}

return true;