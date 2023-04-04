<?php

namespace Zorgportal\Admin\Screen;

use Zorgportal\App;

class Screen
{
    protected $errors = [];

    public function __construct( App $appContext )
    {
        $this->appContext = $appContext;
    }

    public function init()
    {
    }

    public function update()
    {
    }

    public function scripts()
    {
    }

    protected function renderTemplate(string $template, array $args=[], $with_errors=true)
    {
        $file = plugin_dir_path( $this->appContext->getPluginFile() ) . sprintf('%1$ssrc%1$stemplates%1$s%2$s', DIRECTORY_SEPARATOR, $template);

        if ( file_exists($file) ) {
            if ( $with_errors ) {
                echo ( join( PHP_EOL, $this->getErrors() ) );
            }

            extract((array) $args);
            include( $file );
        }
    }

    public function error(string $message) : self
    {
        $this->errors []= '<div class="notice is-dismissible error"><p>' . $message . '</p></div>';
        return $this;
    }

    public function success(string $message) : self
    {
        $this->errors []= '<div class="notice is-dismissible updated"><p>' . $message . '</p></div>';
        return $this;
    }

    public function info(string $message) : self
    {
        $this->errors []= '<div class="notice is-dismissible notice-info"><p>' . $message . '</p></div>';
        return $this;
    }

    public function resetErrors() : self
    {
        $this->errors = [];
        return $this;
    }

    public function getErrors() : array
    {
        return $this->errors;
    }
}