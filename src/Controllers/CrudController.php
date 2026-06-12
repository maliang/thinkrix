<?php

namespace Thinkrix\Controllers;

use think\Request;
use think\Db;
use Thinkrix\Exports\BaseExport;

/**
 * CrudController - CRUD 控制器基类
 */
abstract class CrudController extends Controller
{
    /**
     * 获取模型类名
     */
    abstract protected function getModelClass(): string;

    /**
     * 获取资源名称（用于错误提示）
     */
    protected function getResourceName(): string
    {
        return '记录';
    }

    /**
     * 获取数据表名
     */
    protected function getTable(): string
    {
        $modelClass = $this->getModelClass();
        $model = new $modelClass;
        return $model->getTable();
    }

    /**
     * 获取主键名
     */
    protected function getPrimaryKey(): string
    {
        return 'id';
    }

    /**
     * 获取默认排序
     */
    protected function getDefaultOrder(): array
    {
        return ['id', 'desc'];
    }

    /**
     * 获取默认分页大小
     */
    protected function getDefaultPageSize(): int
    {
        return 15;
    }

    /**
     * 获取列表预加载关联
     */
    protected function getListWith(): array
    {
        return [];
    }

    /**
     * 获取详情预加载关联
     */
    protected function getShowWith(): array
    {
        return $this->getListWith();
    }

    /**
     * 获取导出列配置
     */
    protected function getExportColumns(): array
    {
        return [];
    }

    /**
     * 获取导出文件名前缀
     */
    protected function getExportFilenamePrefix(): string
    {
        return '导出数据';
    }

    // ==================== 路由方法 ====================

    /**
     * 列表入口（支持 action_type 分发）
     */
    public function index(): mixed
    {
        $actionType = $this->input('action_type', 'list');

        return match ($actionType) {
            'export' => $this->export(),
            'batch_destroy' => $this->batchDestroy(),
            'list_ui' => $this->listUi(),
            'form_ui' => $this->formUi(),
            default => $this->list(),
        };
    }

    /**
     * 创建资源
     */
    public function store(): array
    {
        $validated = $this->validateStore();
        $model = $this->performStore($validated);
        $this->afterStore($model, $validated);

        return success('创建成功', $model->toArray());
    }

    /**
     * 显示资源详情
     */
    public function show(int $id): array
    {
        $model = $this->findOrFail($id);
        return success($model->toArray());
    }

    /**
     * 更新入口（支持 action_type 分发）
     */
    public function update(int $id): array
    {
        $actionType = $this->input('action_type', 'update');

        $customMethod = 'update' . str_replace('_', '', ucwords($actionType, '_'));
        if ($actionType !== 'update' && method_exists($this, $customMethod)) {
            return $this->$customMethod($id);
        }

        return match ($actionType) {
            'status' => $this->updateStatus($id),
            default => $this->updateModel($id),
        };
    }

    /**
     * 删除入口（支持 action_type 分发）
     */
    public function destroy(int $id = 0): array
    {
        $actionType = $this->input('action_type', 'delete');

        if ($actionType === 'batch') {
            return $this->batchDestroy();
        }

        return $this->deleteModel($id);
    }

    // ==================== 列表相关 ====================

    /**
     * 获取列表数据
     */
    protected function list(): array
    {
        $query = $this->buildListQuery();

        $perPage = (int) $this->input('page_size', $this->getDefaultPageSize());
        $paginator = $query->paginate($perPage);

        return success([
            'list' => $paginator->getCollection()->toArray(),
            'total' => $paginator->total(),
            'page' => $paginator->currentPage(),
            'page_size' => $paginator->listRows(),
        ]);
    }

    /**
     * 构建列表查询
     */
    protected function buildListQuery()
    {
        $modelClass = $this->getModelClass();
        $query = $modelClass::whereRaw('1 = 1');

        $this->applyResourceScope($query);
        $this->applySearch($query);
        $this->applyFilters($query);

        if ($with = $this->getListWith()) {
            $query->with($with);
        }

        [$orderColumn, $orderDirection] = $this->getDefaultOrder();
        $query->order($orderColumn, $orderDirection);

        return $query;
    }

    /**
     * 应用搜索条件（子类重写）
     */
    protected function applySearch($query): void {}

    /**
     * 应用筛选条件（子类重写）
     */
    protected function applyFilters($query): void
    {
        if ($this->input('status') !== null && $this->input('status') !== '') {
            $query->where('status', (int) $this->input('status'));
        }
    }

    /**
     * 应用资源级隔离条件（如 guard、租户）。
     */
    protected function applyResourceScope($query): void {}

    // ==================== 创建相关 ====================

    /**
     * 验证创建数据（子类重写）
     */
    protected function validateStore(): array
    {
        $rules = $this->getStoreRules();
        $data = request()->post();
        return $this->validate($data, $rules);
    }

    /**
     * 获取创建验证规则（子类重写）
     */
    protected function getStoreRules(): array
    {
        return [];
    }

    /**
     * 执行创建操作
     */
    protected function performStore(array $validated)
    {
        $modelClass = $this->getModelClass();
        $data = $this->prepareStoreData($validated);
        return $modelClass::create($data);
    }

    /**
     * 准备创建数据（子类可重写）
     */
    protected function prepareStoreData(array $validated): array
    {
        return $validated;
    }

    /**
     * 创建后回调（子类可重写）
     */
    protected function afterStore($model, array $validated): void {}

    // ==================== 更新相关 ====================

    /**
     * 更新模型
     */
    protected function updateModel(int $id): array
    {
        $model = $this->findOrFail($id);
        $validated = $this->validateUpdate($id);

        $model->save($this->prepareUpdateData($validated));
        $this->afterUpdate($model, $validated);

        return success('更新成功', $model->toArray());
    }

    /**
     * 验证更新数据（子类重写）
     */
    protected function validateUpdate(int $id): array
    {
        $rules = $this->getUpdateRules($id);
        $data = request()->put();
        return $this->validate($data, $rules);
    }

    /**
     * 获取更新验证规则（子类重写）
     */
    protected function getUpdateRules(int $id): array
    {
        return [];
    }

    /**
     * 准备更新数据（子类可重写）
     */
    protected function prepareUpdateData(array $validated): array
    {
        return $validated;
    }

    /**
     * 更新后回调（子类可重写）
     */
    protected function afterUpdate($model, array $validated): void {}

    /**
     * 更新状态
     */
    protected function updateStatus(int $id): array
    {
        $model = $this->findOrFail($id);

        $data = request()->put();
        $this->validate($data, ['status' => 'require|boolean']);

        $model->status = $data['status'];
        $model->save();

        $this->afterStatusUpdate($model, $data['status']);

        return success('状态更新成功', ['status' => $model->status]);
    }

    /**
     * 状态更新后回调（子类可重写）
     */
    protected function afterStatusUpdate($model, bool $status): void {}

    // ==================== 删除相关 ====================

    /**
     * 删除单个模型
     */
    protected function deleteModel(int $id): array
    {
        $model = $this->findOrFail($id);

        $this->beforeDelete($model);
        $model->delete();
        $this->afterDelete($model);

        return success('删除成功');
    }

    /**
     * 批量删除
     */
    protected function batchDestroy(): array
    {
        $data = request()->post();
        $this->validate($data, [
            'ids' => 'require|array|min:1',
            'ids.*' => 'integer',
        ]);

        $modelClass = $this->getModelClass();
        $pk = $this->getPrimaryKey();
        $query = $modelClass::whereIn($pk, $data['ids']);
        $this->applyResourceScope($query);
        $models = $query->select();

        if ($models->isEmpty()) {
            return error("未找到要删除的{$this->getResourceName()}");
        }

        foreach ($models as $model) {
            $this->beforeDelete($model);
        }

        $deleteQuery = $modelClass::whereIn($pk, $data['ids']);
        $this->applyResourceScope($deleteQuery);
        $deleted = $deleteQuery->delete();

        return success('批量删除成功', ['deleted' => $deleted]);
    }

    /**
     * 删除前回调（子类可重写）
     */
    protected function beforeDelete($model): void {}

    /**
     * 删除后回调（子类可重写）
     */
    protected function afterDelete($model): void {}

    // ==================== 导出相关 ====================

    /**
     * 导出数据
     */
    protected function export()
    {
        $query = $this->buildListQuery();
        $type = $this->input('type', 'current');
        $prefix = $this->getExportFilenamePrefix();

        if ($type === 'current') {
            $page = (int) $this->input('page', 1);
            $pageSize = (int) $this->input('page_size', $this->getDefaultPageSize());
            $data = $query->page($page, $pageSize)->select();
            $filename = "{$prefix}_第{$page}页_" . date('YmdHis') . '.xlsx';
        } else {
            $data = $query->select();
            $filename = "{$prefix}_全部_" . date('YmdHis') . '.xlsx';
        }

        $columns = $this->getExportColumns();

        $export = new BaseExport($data, $columns);
        return $export->download($filename);
    }

    // ==================== UI Schema ====================

    /**
     * 列表页 UI Schema（子类重写）
     */
    protected function listUi(): array
    {
        return success([]);
    }

    /**
     * 表单页 UI Schema（子类重写）
     */
    protected function formUi(): array
    {
        return success([]);
    }

    // ==================== 辅助方法 ====================

    /**
     * 查找模型或抛出错误
     */
    protected function findOrFail(int $id)
    {
        $modelClass = $this->getModelClass();
        $query = $modelClass::where($this->getPrimaryKey(), $id);
        $this->applyResourceScope($query);
        $model = $query->find();

        if (!$model) {
            throw new \Thinkrix\Exceptions\ApiException("{$this->getResourceName()}不存在", 40004);
        }

        return $model;
    }
}
