<?php

/**
 * Minimal stubs for classes that Icinga Web 2 / Icinga Director provide at
 * runtime and that this module is not composer-installed against. They
 * exist only so Phan can resolve class shapes during static analysis and
 * are never shipped as part of the module (see .phan/config.php).
 */

namespace Icinga\Application\Modules {
    class Module
    {
        public function provideHook(string $name): void
        {
        }
    }
}

namespace Icinga\Module\Director\Hook {
    abstract class ImportSourceHook
    {
        protected function getSetting(string $name, $default = null)
        {
        }

        abstract public function getName();

        abstract public function fetchData();

        abstract public function listColumns();
    }
}

namespace Icinga\Module\Director\Web\Form {
    class QuickForm
    {
        public function addElement(string $type, string $name, array $options = []): void
        {
        }

        public function translate(string $message): string
        {
        }
    }
}
