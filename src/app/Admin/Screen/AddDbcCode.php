<?php

namespace Zorgportal\Admin\Screen;

use Zorgportal\DbcCodes as Codes;

class AddDbcCode extends EditDbcCode
{
    protected $clone;

    public function init()
    {
        if ( $clone_id = intval($_GET['clone_id'] ?? '') ) {
            if ( 'POST' !== ($_SERVER['REQUEST_METHOD'] ?? '') )
                $this->clone = Codes::queryOne(['id' => $clone_id]);
        }

        $this->code = [];
    }
}