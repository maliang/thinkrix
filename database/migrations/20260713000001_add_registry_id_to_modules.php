<?php

use think\migration\Migrator;

/** 为已安装模块补充稳定的生态 Registry ID。 */
class AddRegistryIdToModules extends Migrator
{
    /** 添加可空唯一字段，旧项目可直接迁移。 */
    public function up(): void
    {
        $table = $this->table('modules');
        if (!$table->hasColumn('registry_id')) {
            $table->addColumn('registry_id', 'string', [
                'limit' => 191,
                'null' => true,
                'default' => null,
                'comment' => '生态模块唯一标识',
                'after' => 'name',
            ])->addIndex(['registry_id'], ['unique' => true, 'name' => 'uk_registry_id'])->update();
        }

        $this->backfillRegistryIds();
    }

    /** 回滚生态标识字段及其唯一索引。 */
    public function down(): void
    {
        $table = $this->table('modules');
        if ($table->hasColumn('registry_id')) {
            $table->removeColumn('registry_id')->update();
        }
    }

    /** 从新协议 module.json.trix 回填旧模块记录。 */
    private function backfillRegistryIds(): void
    {
        foreach ((array) config('thinkrix.modules.paths', ['Modules', 'app']) as $root) {
            $pattern = app()->getRootPath() . trim((string) $root, '/\\') . DIRECTORY_SEPARATOR . '*' . DIRECTORY_SEPARATOR . 'module.json';
            foreach (glob($pattern) ?: [] as $manifestPath) {
                $manifest = json_decode((string) file_get_contents($manifestPath), true);
                $trix = is_array($manifest) && is_array($manifest['trix'] ?? null) ? $manifest['trix'] : null;
                $registryId = is_array($trix) ? trim((string) ($trix['id'] ?? '')) : '';
                if ($registryId === '') {
                    continue;
                }

                $name = basename(dirname($manifestPath));
                $record = app()->db->name('modules')->where('name', $name)->find();
                if (is_array($record) && empty($record['registry_id'])) {
                    app()->db->name('modules')->where('id', $record['id'])->update(['registry_id' => $registryId]);
                }
            }
        }
    }
}
