<?php

use think\facade\Route;

Route::group('/', function () {
    Route::rule('list', 'index/index/lists');
    Route::rule('show', 'index/index/show');
    Route::rule('tag_list', 'index/index/tag_list');
    Route::rule('list/:catid/[:condition]', 'index/index/lists')->pattern([
        'catid'     => '\d+',
        'condition' => '[0-9_&=a-zA-Z]+'
    ]);
    Route::rule('show/:catid/:id', 'index/index/show')->pattern(['catid' => '\d+', 'id' => '\d+']);
    Route::rule('tag/[:tag]', 'index/index/tags');
    Route::rule('search', 'index/index/search');
    Route::rule('link_apply', 'index/index/link_apply');
    Route::rule('project_add', 'index/index/project_add');
});