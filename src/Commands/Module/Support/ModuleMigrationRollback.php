<?php

namespace Thinkrix\Commands\Module\Support;

use Phinx\Migration\MigrationInterface;

class ModuleMigrationRollback extends \think\migration\command\migrate\Rollback
{
    public function __construct(protected string $migrationPath)
    {
        parent::__construct();
    }

    protected function getPath(): string
    {
        return $this->migrationPath;
    }

    protected function rollback($version = null, $force = false)
    {
        $migrations = $this->getMigrations();
        $versionLog = $this->getVersionLog();
        $appliedVersions = array_values(array_intersect(array_keys($migrations), array_keys($versionLog)));
        sort($appliedVersions);

        if ($appliedVersions === []) {
            $this->output->writeln('<comment>No module migrations to rollback</comment>');
            return;
        }

        if ($version === null) {
            array_pop($appliedVersions);
            $version = $appliedVersions === [] ? 0 : end($appliedVersions);
        }

        krsort($migrations);
        foreach ($migrations as $migration) {
            if ($migration->getVersion() <= $version) {
                break;
            }
            if (isset($versionLog[$migration->getVersion()])) {
                $this->executeMigration($migration, MigrationInterface::DOWN);
            }
        }
    }
}
