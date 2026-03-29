@extends('advancedreports::layouts.app')
@section('title', __('advancedreports::lang.award_catalog_management'))

@section('content')

<!-- Content Header -->
<section class="content-header no-print">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">
        {{ __('advancedreports::lang.award_catalog_management')}}
        <small class="text-muted">@lang('advancedreports::lang.manage_gifts_awards_customer_recognition')</small>
    </h1>
</section>

<!-- Main content -->
<section class="content no-print">

    <!-- Action Buttons -->
    <div class="row">
        <div class="col-md-12">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <i class="fa fa-gift"></i> @lang('advancedreports::lang.award_catalog_controls')
                    </h3>
                </div>
                <div class="box-body">
                    <div class="row">
                        <div class="col-md-12">
                            <button type="button" class="btn btn-primary" id="add_catalog_item_btn">
                                <i class="fa fa-plus"></i> @lang('advancedreports::lang.add_new_award')
                            </button>
                            <button type="button" class="btn btn-info" id="refresh_catalog_btn">
                                <i class="fa fa-refresh"></i> @lang('advancedreports::lang.refresh')
                            </button>
                            <button type="button" class="btn btn-success" id="export_catalog_btn">
                                <i class="fa fa-download"></i> @lang('advancedreports::lang.export_catalog')
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Data Table -->
    <div class="row">
        <div class="col-md-12">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">
                        <i class="fa fa-list"></i> @lang('advancedreports::lang.award_catalog_items')
                    </h3>
                </div>
                <div class="box-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="award_catalog_table">
                            <thead>
                                <tr>
                                    <th>@lang('advancedreports::lang.award_name')</th>
                                    <th>@lang('advancedreports::lang.product_link')</th>
                                    <th>@lang('advancedreports::lang.value')</th>
                                    <th>@lang('advancedreports::lang.stock_status')</th>
                                    <th>@lang('advancedreports::lang.point_threshold')</th>
                                    <th>@lang('advancedreports::lang.active')</th>
                                    <th>@lang(key: 'advancedreports::lang.action')</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Add/Edit Catalog Item Modal -->
<div class="modal fade catalog_item_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="catalog_modal_title">@lang('advancedreports::lang.add_award_item')</h4>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="catalog_item_form" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" id="catalog_item_id" name="catalog_item_id">

                    <!-- Basic Information -->
                    <div class="row">
                        <div class="col-md-12">
                            <h5><i class="fa fa-info-circle"></i> @lang('advancedreports::lang.basic_information')</h5>
                            <hr>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group">
                                <label for="award_name">@lang('advancedreports::lang.award_name'): <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="award_name" name="award_name" required
                                    placeholder="@lang('advancedreports::lang.eg_gift_voucher')"
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="monetary_value">@lang('advancedreports::lang.monetary_value'): <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-addon">$</span>
                                    <input type="number" class="form-control" id="monetary_value" name="monetary_value"
                                        step="0.01" min="0" required>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="description">@lang('advancedreports::lang.description'):</label>
                                <textarea class="form-control" id="description" name="description" rows="3"
                                    placeholder="@lang('advancedreports::lang.detailed_description_award')"></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Product Linking -->
                    <div class="row">
                        <div class="col-md-12">
                            <h5><i class="fa fa-link"></i> @lang('advancedreports::lang.product_integration')</h5>
                            <hr>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="product_id">@lang('advancedreports::lang.link_to_product_optional'):</label>
                                {!! Form::select('product_id', $products, null, ['class' => 'form-control select2', 'id'
                                => 'product_id', 'placeholder' => 'Select Product']) !!}
                                <small class="text-muted">Link to existing product in inventory</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="point_threshold">@lang('advancedreports::lang.point_threshold'):</label>
                                <input type="number" class="form-control" id="point_threshold" name="point_threshold"
                                    min="0" placeholder="0">
                                <small class="text-muted">@lang('advancedreports::lang.min_engagement_points_qualify')</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>&nbsp;</label>
                                <div class="checkbox">
                                    <label>
                                        <input type="checkbox" id="is_active" name="is_active" checked> Active Award
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Stock Management -->
                    <div class="row">
                        <div class="col-md-12">
                            <h5><i class="fa fa-cubes"></i> @lang('advancedreports::lang.stock_management')</h5>
                            <hr>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <div class="checkbox">
                                    <label>
                                        <input type="checkbox" id="stock_required" name="stock_required">
                                        <strong>@lang('advancedreports::lang.requires_stock_management')</strong>
                                    </label>
                                </div>
                                <small class="text-muted">Check if this award has limited quantity</small>
                            </div>
                        </div>
                        <div class="col-md-4" id="stock_quantity_field" style="display: none;">
                            <div class="form-group">
                                <label for="stock_quantity">@lang('advancedreports::lang.stock_quantity'):</label>
                                <input type="number" class="form-control" id="stock_quantity" name="stock_quantity"
                                    min="0" placeholder="0">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="award_image">@lang('advancedreports::lang.award_image'):</label>
                                <input type="file" class="form-control" id="award_image" name="award_image"
                                    accept="image/*">
                                <small class="text-muted">JPG, PNG, GIF (Max 2MB)</small>
                            </div>
                        </div>
                    </div>

                    <!-- Image Preview -->
                    <div class="row" id="image_preview_row" style="display: none;">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>@lang('advancedreports::lang.current_image'):</label>
                                <div>
                                    <img id="current_image" src="" alt="Award Image"
                                        style="max-height: 150px; border: 1px solid #ddd; border-radius: 4px;">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa fa-save"></i> <span id="catalog_submit_text">Save Award</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade delete_catalog_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">@lang('advancedreports::lang.confirm_deletion')</h4>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    <i class="fa fa-warning"></i>
                    <strong>@lang('advancedreports::lang.warning'):</strong> @lang('advancedreports::lang.are_you_sure_delete_award_item')
                </div>
                <p><strong>Award:</strong> <span id="delete_award_name"></span></p>
                <p class="text-muted">@lang('advancedreports::lang.award_used_deactivated_instead_deleted')</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirm_delete_catalog">
                    <i class="fa fa-trash"></i> @lang('advancedreports::lang.delete_award')
                </button>
            </div>
        </div>
    </div>
</div>

@stop

@section('javascript')
<script type="text/javascript">
    $(document).ready(function() {
        // Initialize Select2
        $('.select2').select2();

        // Initialize DataTable
        var award_catalog_table = $('#award_catalog_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('advancedreports.award-catalog.data') }}",
                type: 'GET'
            },
            columns: [
                { data: 'award_name', name: 'award_name', width: '25%' },
                { data: 'product_id', name: 'product_id', width: '15%' },
                { data: 'monetary_value', name: 'monetary_value', width: '12%', className: 'text-right' },
                { data: 'stock_required', name: 'stock_required', width: '15%', className: 'text-center' },
                { data: 'point_threshold', name: 'point_threshold', width: '10%', className: 'text-center' },
                { data: 'is_active', name: 'is_active', width: '8%', className: 'text-center', orderable: false },
                { data: 'action', name: 'action', width: '15%', orderable: false, searchable: false }
            ],
            order: [[0, 'asc']],
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]]
        });

        // Stock required checkbox change
        $('#stock_required').on('change', function() {
            if ($(this).is(':checked')) {
                $('#stock_quantity_field').show();
                $('#stock_quantity').attr('required', true);
            } else {
                $('#stock_quantity_field').hide();
                $('#stock_quantity').attr('required', false).val('');
            }
        });

        // Add new catalog item
        $('#add_catalog_item_btn').click(function() {
            resetCatalogForm();
            $('#catalog_modal_title').text(@json(__('advancedreports::lang.add_new_award')));
            $('#catalog_submit_text').text('Save Award');
            $('.catalog_item_modal').modal('show');
        });

        // Edit catalog item
        $(document).on('click', '.edit-catalog-item', function() {
            var id = $(this).data('id');
            editCatalogItem(id);
        });

        // Delete catalog item
        $(document).on('click', '.delete-catalog-item', function() {
            var id = $(this).data('id');
            var name = $(this).data('name');
            
            $('#delete_award_name').text(name);
            $('#confirm_delete_catalog').data('id', id);
            $('.delete_catalog_modal').modal('show');
        });

        // Confirm delete
        $('#confirm_delete_catalog').click(function() {
            var id = $(this).data('id');
            deleteCatalogItem(id);
        });

        // Toggle active status
        $(document).on('change', '.toggle-active', function() {
            var id = $(this).data('id');
            var isActive = $(this).is(':checked');
            
            $.ajax({
                url: "{{ route('advancedreports.award-catalog.toggle-active') }}",
                method: 'POST',
                data: {
                    id: id,
                    is_active: isActive
                },
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success) {
                        toastr.success('Status updated successfully');
                    }
                },
                error: function(xhr) {
                    var message = xhr.responseJSON?.error || 'Error updating status';
                    toastr.error(message);
                    // Revert checkbox
                    $(this).prop('checked', !isActive);
                }
            });
        });

        // Form submission
        $('#catalog_item_form').on('submit', function(e) {
            e.preventDefault();
            
            var formData = new FormData(this);
            var id = $('#catalog_item_id').val();
            var url = id ? 
                "{{ route('advancedreports.award-catalog.update', '') }}/" + id :
                "{{ route('advancedreports.award-catalog.store') }}";
            var method = id ? 'PUT' : 'POST';
            
            // Add method for PUT requests
            if (method === 'PUT') {
                formData.append('_method', 'PUT');
            }
            
            $.ajax({
                url: url,
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message);
                        $('.catalog_item_modal').modal('hide');
                        award_catalog_table.ajax.reload();
                    }
                },
                error: function(xhr) {
                    var message = xhr.responseJSON?.error || 'Error saving award item';
                    toastr.error(message);
                    
                    // Show validation errors
                    if (xhr.responseJSON?.errors) {
                        var errors = xhr.responseJSON.errors;
                        for (var field in errors) {
                            var input = $('#' + field);
                            input.addClass('is-invalid');
                            input.after('<div class="invalid-feedback">' + errors[field][0] + '</div>');
                        }
                    }
                }
            });
        });

        // Refresh catalog
        $('#refresh_catalog_btn').click(function() {
            award_catalog_table.ajax.reload();
            toastr.info('Catalog refreshed');
        });

        // Export catalog
        $('#export_catalog_btn').click(function(e) {
            e.preventDefault();

            var $btn = $(this);
            var originalText = $btn.html();
            $btn.html('<i class="fa fa-spinner fa-spin"></i> {{ __("advancedreports::lang.exporting") }}').prop('disabled', true);

            var params = {
                _token: '{{ csrf_token() }}'
            };

            // Use AJAX to download the file properly
            $.ajax({
                url: "{{ route('advancedreports.award-catalog.export') }}",
                type: 'POST',
                data: params,
                xhrFields: {
                    responseType: 'blob'
                },
                success: function(data, status, xhr) {
                    // Create blob link to download
                    var blob = new Blob([data], {
                        type: xhr.getResponseHeader('Content-Type') || 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
                    });

                    var link = document.createElement('a');
                    link.href = window.URL.createObjectURL(blob);

                    // Get filename from response header or use default
                    var filename = 'award-catalog.xlsx';
                    var disposition = xhr.getResponseHeader('Content-Disposition');
                    if (disposition && disposition.indexOf('attachment') !== -1) {
                        var filenameRegex = /filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/;
                        var matches = filenameRegex.exec(disposition);
                        if (matches != null && matches[1]) {
                            filename = matches[1].replace(/['"]/g, '');
                        }
                    }
                    link.download = filename;

                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);

                    // Clean up
                    window.URL.revokeObjectURL(link.href);
                },
                error: function(xhr, status, error) {
                    alert('Export failed: ' + error);
                },
                complete: function() {
                    setTimeout(function() {
                        $btn.html(originalText).prop('disabled', false);
                    }, 1000);
                }
            });
        });

        // Reset form function
        function resetCatalogForm() {
            $('#catalog_item_form')[0].reset();
            $('#catalog_item_id').val('');
            $('#stock_quantity_field').hide();
            $('#image_preview_row').hide();
            $('.is-invalid').removeClass('is-invalid');
            $('.invalid-feedback').remove();
            $('#product_id').val('').trigger('change');
        }

        // Edit catalog item function
        function editCatalogItem(id) {
            // This would typically fetch the item data via AJAX
            // For now, we'll show the modal and populate it
            resetCatalogForm();
            $('#catalog_modal_title').text(@json(__('advancedreports::lang.edit_award')));
            $('#catalog_submit_text').text('Update Award');
            $('#catalog_item_id').val(id);
            
            // TODO: Fetch and populate item data
            $.ajax({
                url: "{{ route('advancedreports.award-catalog.show', '') }}/" + id,
                method: 'GET',
                success: function(data) {
                    if (data.success) {
                        var item = data.item;
                        $('#award_name').val(item.award_name);
                        $('#description').val(item.description);
                        $('#monetary_value').val(item.monetary_value);
                        $('#point_threshold').val(item.point_threshold);
                        $('#stock_quantity').val(item.stock_quantity);
                        $('#is_active').prop('checked', item.is_active);
                        $('#stock_required').prop('checked', item.stock_required);
                        $('#product_id').val(item.product_id).trigger('change');
                        
                        if (item.stock_required) {
                            $('#stock_quantity_field').show();
                        }
                        
                        if (item.award_image) {
                            $('#current_image').attr('src', '/storage/' + item.award_image);
                            $('#image_preview_row').show();
                        }
                    }
                },
                error: function() {
                    toastr.error('Error loading award data');
                }
            });
            
            $('.catalog_item_modal').modal('show');
        }

        // Delete catalog item function
        function deleteCatalogItem(id) {
            $.ajax({
                url: "{{ route('advancedreports.award-catalog.destroy', '') }}/" + id,
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message);
                        $('.delete_catalog_modal').modal('hide');
                        award_catalog_table.ajax.reload();
                    }
                },
                error: function(xhr) {
                    var message = xhr.responseJSON?.error || 'Error deleting award item';
                    toastr.error(message);
                }
            });
        }

        // File input change event for image preview
        $('#award_image').on('change', function() {
            var file = this.files[0];
            if (file) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    $('#current_image').attr('src', e.target.result);
                    $('#image_preview_row').show();
                };
                reader.readAsDataURL(file);
            }
        });

        // Clear validation errors on input
        $('.form-control').on('input change', function() {
            $(this).removeClass('is-invalid');
            $(this).next('.invalid-feedback').remove();
        });
    });
</script>

<style>
    /* Form styling */
    .form-group label {
        font-weight: 600;
        color: #495057;
    }

    .form-control:focus {
        border-color: #3498db;
        box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
    }

    .is-invalid {
        border-color: #dc3545;
    }

    .invalid-feedback {
        display: block;
        color: #dc3545;
        font-size: 0.875em;
        margin-top: 0.25rem;
    }

    /* Modal styling */
    .modal-lg {
        max-width: 900px;
    }

    .modal-header {
        background-color: #f8f9fa;
        border-bottom: 1px solid #dee2e6;
    }

    .modal-title {
        font-weight: 600;
        color: #495057;
    }

    /* Box styling */
    .box {
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        border: none;
    }

    .box-header {
        border-radius: 8px 8px 0 0;
        padding: 15px;
    }

    .box-primary .box-header {
        background-color: #3498db;
        color: white;
    }

    .box-primary .box-header .box-title {
        color: white;
    }

    /* Table styling */
    .table th {
        background-color: #f4f4f4;
        font-weight: 600;
        color: #444;
        border-bottom: 2px solid #ddd;
    }

    /* Button styling */
    .btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
    }

    .btn-xs {
        padding: 2px 6px;
        font-size: 11px;
        margin-right: 2px;
    }

    /* Alert styling */
    .alert {
        border-radius: 6px;
        border: none;
    }

    .alert-warning {
        background-color: #fff3cd;
        color: #856404;
        border-left: 4px solid #ffc107;
    }

    /* Input group styling */
    .input-group-addon {
        background-color: #f8f9fa;
        border: 1px solid #ced4da;
        color: #6c757d;
    }

    /* Checkbox styling */
    .checkbox label {
        font-weight: normal;
        margin-bottom: 0;
    }

    /* Badge styling */
    .badge {
        font-size: 11px;
        padding: 3px 6px;
    }

    .badge-info {
        background-color: #17a2b8;
    }

    .badge-success {
        background-color: #28a745;
    }

    .badge-danger {
        background-color: #dc3545;
    }

    /* Label styling */
    .label {
        font-size: 10px;
        padding: 3px 6px;
        border-radius: 3px;
    }

    .label-success {
        background-color: #28a745;
    }

    .label-default {
        background-color: #6c757d;
    }

    .label-info {
        background-color: #17a2b8;
    }

    .label-danger {
        background-color: #dc3545;
    }

    /* Image preview styling */
    #current_image {
        max-width: 100%;
        height: auto;
        border-radius: 4px;
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .modal-lg {
            max-width: 95%;
            margin: 10px auto;
        }

        .btn-xs {
            padding: 4px 8px;
            font-size: 12px;
        }
    }

    /* Loading states */
    .fa-spinner {
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        0% {
            transform: rotate(0deg);
        }

        100% {
            transform: rotate(360deg);
        }
    }

    /* Section headers */
    h5 {
        color: #495057;
        font-weight: 600;
        margin-bottom: 10px;
    }

    h5 i {
        margin-right: 8px;
        color: #3498db;
    }

    /* Required field indicator */
    .text-danger {
        color: #dc3545 !important;
    }

    /* Help text */
    .text-muted {
        color: #6c757d !important;
        font-size: 0.875em;
    }
</style>
@endsection