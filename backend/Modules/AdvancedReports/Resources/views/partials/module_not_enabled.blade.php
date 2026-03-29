@extends('layouts.app')

@section('title', __('advancedreports::lang.advanced_reports'))

@section('content')
<section class="content-header">
    <h1>@lang('advancedreports::lang.advanced_reports')</h1>
</section>

<section class="content">
    <div class="row">
        <div class="col-md-12">
            <div class="box box-warning">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <i class="fa fa-exclamation-triangle"></i>
                        @lang('advancedreports::lang.module_not_enabled')
                    </h3>
                </div>
                <div class="box-body">
                    <div class="alert alert-warning">
                        <h4><i class="icon fa fa-warning"></i> @lang('advancedreports::lang.alert')</h4>
                        {{ $output['msg'] }}
                    </div>

                    <div class="text-center">
                        <p class="lead">
                            <i class="fa fa-cogs fa-3x text-warning"></i>
                        </p>
                        <h4>@lang('advancedreports::lang.module_required')</h4>
                        <p class="text-muted">
                            @lang('advancedreports::lang.enable_module_instruction')
                        </p>

                        <div class="margin-top">
                            <a href="{{ url('/business/settings') }}" class="btn btn-primary btn-lg">
                                <i class="fa fa-cog"></i>
                                @lang('advancedreports::lang.go_to_settings')
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection