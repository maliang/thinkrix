<?php

namespace Thinkrix\Commands\Module\Support;

use Phinx\Migration\MigrationInterface;

class ModuleMigrationRun extends \think\migration\command\migrate\Run
{
    public function __construct(protected string $migrationPath)
    {
        parent::__construct();
    }

    protected function getPath(): string
    {
        return $this->migrationPath;
    }

    protected function migrate($version = null)
    {
        $migrations = $this->getMigrations();
        $appliedVersions = $this->getVersions();
        ksort($migrations);

        foreach ($migrations as $migration) {
            if ($version !== null && $migration->getVersion() > $version) {
                break;
            }
            if (!in_array($migration->getVersion(), $appliedVersions)) {
                $this->executeMigration($migration, MigrationInterface::UP);
            }
        }
    }
}
