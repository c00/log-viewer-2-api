<?php

namespace c00\logViewer;


use c00\common\AbstractSettings;
use c00\log\channel\sql\SqlSettings;

class Settings extends AbstractSettings
{
    /** @var SqlSettings[] */
    public $databases = [];

    public function loadDefaults()
    {
        //no default values.
        $this->databases = [];

    }

}