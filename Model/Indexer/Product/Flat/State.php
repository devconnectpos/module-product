<?php
/**
 * Created by PhpStorm.
 * User: xuantung
 * Date: 11/13/18
 * Time: 10:24 AM
 */

namespace SM\Product\Model\Indexer\Product\Flat;

use Magento\Framework\Registry;

class State
{
    /**
     * @var \Magento\Framework\Registry
     */
    private $registry;

    public function __construct(
        Registry $registry
    ) {
        $this->registry = $registry;
    }

    public function afterIsFlatEnabled()
    {
        if ($this->registry->registry('disableFlatProduct')) {
            return false;
        }
        return true;
    }
}
