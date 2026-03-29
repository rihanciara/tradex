@extends('layouts.app')

@section('title', __('exchange::lang.exchange_list'))

@section('content')
<section class="content-header no-print">
    <h1>@lang('exchange::lang.exchange')
        <small>@lang('exchange::lang.manage_your_exchanges')</small>
    </h1>
</section>

<section class="content no-print">
    <!-- Filters Component -->
    @component('components.widget', ['class' => 'box-solid', 'title' => __('report.filters')])
    @slot('tool')
    <div class="box-tools">
        <button type="button" class="btn btn-box-tool" data-widget="collapse">
            <i class="fa fa-minus"></i>
        </button>
    </div>
    @endslot

    <div class="row">
        <!-- Location Filter -->
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('exchange_list_filter_location_id', __('business.business_location') . ':') !!}
                {!! Form::select('exchange_list_filter_location_id', $business_locations, null, ['class' =>
                'form-control select2', 'style' => 'width:100%', 'placeholder' => __('exchange::lang.all')]) !!}
            </div>
        </div>

        <!-- Customer Filter -->
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('exchange_list_filter_customer_id', __('contact.customer') . ':') !!}
                {!! Form::select('exchange_list_filter_customer_id', [], null, ['class' => 'form-control select2',
                'style' => 'width:100%', 'placeholder' => __('exchange::lang.all')]) !!}
            </div>
        </div>

        <!-- Status Filter -->
        <div class="col-md-2">
            <div class="form-group">
                {!! Form::label('exchange_list_filter_status', __('sale.status') . ':') !!}
                {!! Form::select('exchange_list_filter_status', [
                'completed' => __('exchange::lang.completed'),
                'cancelled' => __('exchange::lang.cancelled')
                ], null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' =>
                __('exchange::lang.all')]) !!}
            </div>
        </div>

        <!-- Date Range Filter -->
        <div class="col-md-2">
            <div class="form-group">
                {!! Form::label('exchange_list_filter_date_range', __('report.date_range') . ':') !!}
                {!! Form::text('exchange_list_filter_date_range', null, ['placeholder' =>
                __('exchange::lang.select_a_date_range'), 'class' => 'form-control', 'readonly']) !!}
            </div>
        </div>

        <!-- Created By Filter -->
        <div class="col-md-2">
            <div class="form-group">
                {!! Form::label('exchange_list_filter_created_by', __('exchange::lang.created_by') . ':') !!}
                {!! Form::select('exchange_list_filter_created_by', $users, null, ['class' => 'form-control select2',
                'style' => 'width:100%', 'placeholder' => __('exchange::lang.all')]) !!}
            </div>
        </div>
    </div>
    @endcomponent

    <!-- Table Component -->
    @component('components.widget', ['class' => 'box-primary', 'title' => __('exchange::lang.all_exchanges')])
    @slot('tool')
    <div class="box-tools">
        @can('exchange.create')
        <a class="btn btn-block btn-primary"
            href="{{action([\Modules\Exchange\Http\Controllers\ExchangeController::class, 'create'])}}">
            <i class="fa fa-plus"></i> @lang('exchange::lang.create_exchange')
        </a>
        @endcan
    </div>
    @endslot

    <div class="table-responsive">
        <table class="table table-bordered table-striped" id="exchanges_table">
            <thead>
                <tr>
                    <th>@lang('exchange::lang.exchange_ref_no')</th>
                    <th>@lang('exchange::lang.exchange_date')</th>
                    <th>@lang('exchange::lang.original_invoice')</th>
                    <th>@lang('exchange::lang.exchange_invoice')</th>
                    <th>@lang('contact.customer')</th>
                    <th>@lang('exchange::lang.exchange_amount')</th>
                    <th>@lang('sale.status')</th>
                    <th>@lang('exchange::lang.created_by')</th>
                    <th>@lang('business.business_location')</th>
                    <th>@lang('messages.action')</th>
                </tr>
            </thead>
        </table>
    </div>
    @endcomponent

    <!-- Modal -->
    <div class="modal fade exchange_details_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
    </div>
</section>
@endsection

@push('stylesheets')
<style>
    /* Styling for cancelled exchanges */
    .cancelled-exchange {
        background-color: #f2dede !important;
        opacity: 0.7;
    }

    .cancelled-exchange td {
        text-decoration: line-through;
        color: #888;
    }

    .cancelled-exchange .label-danger {
        text-decoration: none;
    }

    /* Fix dropdown menu visibility and styling */
    .table .btn-group {
        position: relative;
    }

    .table .btn-group .dropdown-menu {
        position: absolute !important;
        top: 100% !important;
        left: 0 !important;
        z-index: 1050 !important;
        display: none;
        float: left;
        min-width: 160px;
        padding: 5px 0;
        margin: 2px 0 0;
        font-size: 14px;
        text-align: left;
        list-style: none;
        background-color: #fff !important;
        background-clip: padding-box;
        border: 1px solid #ccc !important;
        border: 1px solid rgba(0, 0, 0, .15);
        border-radius: 4px;
        box-shadow: 0 6px 12px rgba(0, 0, 0, .175) !important;
    }

    .table .btn-group .dropdown-menu.show,
    .table .btn-group .open>.dropdown-menu {
        display: block !important;
    }

    .table .btn-group .dropdown-menu>li>a {
        display: block !important;
        padding: 3px 20px !important;
        clear: both !important;
        font-weight: normal !important;
        line-height: 1.42857143 !important;
        color: #333 !important;
        white-space: nowrap !important;
        text-decoration: none !important;
        border: none !important;
        background: transparent !important;
    }

    .table .btn-group .dropdown-menu>li>a:hover,
    .table .btn-group .dropdown-menu>li>a:focus {
        color: #262626 !important;
        text-decoration: none !important;
        background-color: #f5f5f5 !important;
    }

    .table .btn-group .dropdown-menu>.disabled>a,
    .table .btn-group .dropdown-menu>.disabled>a:hover,
    .table .btn-group .dropdown-menu>.disabled>a:focus {
        color: #777 !important;
        cursor: not-allowed !important;
        background-color: transparent !important;
        pointer-events: none !important;
    }
</style>
@endpush

@section('javascript')
<script>
    $(document).ready(function() {
        // Initialize date range picker
        if ($('#exchange_list_filter_date_range').length == 1) {
            $('#exchange_list_filter_date_range').daterangepicker(
                dateRangeSettings,
                function(start, end) {
                    $('#exchange_list_filter_date_range').val(
                        start.format(moment_date_format) + ' - ' + end.format(moment_date_format)
                    );
                    exchanges_table.ajax.reload();
                }
            );
            $('#exchange_list_filter_date_range').on('cancel.daterangepicker', function(ev, picker) {
                $('#exchange_list_filter_date_range').val('');
                exchanges_table.ajax.reload();
            });
        }

        // Initialize customer select2
        $('#exchange_list_filter_customer_id').select2({
            ajax: {
                url: '/contacts/customers',
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return {
                        q: params.term,
                        page: params.page
                    };
                },
                processResults: function(data, params) {
                    return {
                        results: data
                    };
                },
                cache: true
            },
            placeholder: '@lang("exchange::lang.all")',
            escapeMarkup: function(markup) {
                return markup;
            },
            minimumInputLength: 1,
            templateResult: function(customer) {
                return customer.text;
            },
            templateSelection: function(customer) {
                return customer.text;
            }
        });

        // Initialize created by select2 (simple, no AJAX)
        $('#exchange_list_filter_created_by').select2({
            placeholder: '@lang("exchange::lang.all")',
            allowClear: true
        });

        // Initialize DataTable with filters
        var exchanges_table = $('#exchanges_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('exchange.list') }}",
                data: function(d) {
                    d.location_id = $('#exchange_list_filter_location_id').val();
                    d.customer_id = $('#exchange_list_filter_customer_id').val();
                    d.status = $('#exchange_list_filter_status').val();
                    d.date_range = $('#exchange_list_filter_date_range').val();
                    d.created_by = $('#exchange_list_filter_created_by').val();
                }
            },
            columnDefs: [
                {
                    targets: 5,
                    orderable: false,
                    searchable: false
                }
            ],
            columns: [
                { data: 'exchange_ref_no', name: 'exchange_ref_no' },
                { data: 'exchange_date', name: 'exchange_date' },
                { data: 'original_invoice', name: 'original_invoice' },
                { data: 'exchange_invoice', name: 'exchange_invoice' },
                { data: 'customer_name', name: 'customer_name' },
                { data: 'total_exchange_amount', name: 'total_exchange_amount' },
                { data: 'status', name: 'status' },
                { data: 'created_by_name', name: 'created_by_name' },
                { data: 'location_name', name: 'location_name' },
                { data: 'action', name: 'action', orderable: false, searchable: false }
            ],
            fnDrawCallback: function(oSettings) {
                __currency_convert_recursively($('#exchanges_table'));
            }
        });

        // Filter change handlers
        $('#exchange_list_filter_location_id, #exchange_list_filter_status').change(function() {
            exchanges_table.ajax.reload();
        });

        $('#exchange_list_filter_customer_id, #exchange_list_filter_created_by').on('change', function() {
            exchanges_table.ajax.reload();
        });

        // Enhanced dropdown handling
        $(document).on('click', '.dropdown-toggle', function(e) {
            e.preventDefault();
            e.stopPropagation();

            // Close all other dropdowns
            $('.dropdown-menu').removeClass('show').hide();

            // Toggle current dropdown
            var $dropdown = $(this).siblings('.dropdown-menu');
            if ($dropdown.hasClass('show')) {
                $dropdown.removeClass('show').hide();
            } else {
                $dropdown.addClass('show').show();
            }
        });

        // Close dropdowns when clicking outside
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.btn-group').length) {
                $('.dropdown-menu').removeClass('show').hide();
            }
        });

        // Prevent dropdown from closing when clicking inside
        $(document).on('click', '.dropdown-menu', function(e) {
            e.stopPropagation();
        });

        // View exchange details modal
        $(document).on('click', '.btn-modal', function(e) {
            e.preventDefault();
            var container = $('.exchange_details_modal');

            $.get($(this).data('href'))
            .done(function(result) {
                container.html(result).modal('show');
            });
        });

        // Handle cancel exchange
        $(document).on('click', '.cancel-exchange', function(e) {
            e.preventDefault();

            var exchange_id = $(this).data('exchange-id');
            var url = $(this).data('href');

            swal({
                title: '@lang("exchange::lang.are_you_sure")',
                text: '@lang("exchange::lang.cancel_exchange_confirmation")',
                icon: "warning",
                buttons: true,
                dangerMode: true,
            }).then((confirmed) => {
                if (confirmed) {
                    $.ajax({
                        url: url,
                        method: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}'
                        },
                        success: function(response) {
                            if (response.success) {
                                toastr.success(response.message);
                                exchanges_table.ajax.reload();
                            } else {
                                toastr.error(response.message);
                            }
                        },
                        error: function(xhr) {
                            var response = xhr.responseJSON;
                            if (response && response.message) {
                                toastr.error(response.message);
                            } else {
                                toastr.error('@lang("messages.something_went_wrong")');
                            }
                        }
                    });
                }
            });
        });


        // Handle delete exchange
$(document).on('click', '.delete-exchange', function(e) {
    e.preventDefault();
    
    var url = $(this).data('href');
    var exchangeId = $(this).data('exchange-id');
    
    swal({
        title: 'Are you sure?',
        text: 'This will permanently delete the exchange. This action cannot be undone!',
        icon: "warning",
        buttons: {
            cancel: {
                text: 'Cancel',
                value: null,
                visible: true,
                className: "btn-default",
                closeModal: true,
            },
            confirm: {
                text: 'Delete',
                value: true,
                visible: true,
                className: "btn-danger",
                closeModal: true
            }
        }
    }).then((willDelete) => {
        if (willDelete) {
            $.ajax({
                url: url,
                type: 'DELETE',
                data: {
                    '_token': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message);
                        exchanges_table.ajax.reload();
                    } else {
                        toastr.error(response.message);
                    }
                },
                error: function(xhr) {
                    var response = JSON.parse(xhr.responseText);
                    toastr.error(response.message || 'An error occurred');
                }
            });
        }
    });
});
    });
</script>
@endsection