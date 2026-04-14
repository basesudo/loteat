@extends('layouts.admin.app')

@section('title',translate('messages.deduct_fund'))

@push('css_or_js')

@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-header-title mr-3">
                <span class="page-header-icon">
                    <img src="{{asset('/public/assets/admin/img/money.png')}}" class="w--26" alt="">
                </span>
                <span>
                     {{translate("messages.deduct_fund")}}
                </span>
            </h1>
        </div>
        <!-- Page Header -->
        <div class="card gx-2 gx-lg-3">
            <div class="card-body">
                <form action="{{route('admin.users.customer.wallet.deduct-fund')}}" method="post" enctype="multipart/form-data" id="deduct_fund">
                    @csrf
                    <div class="row">
                        <div class="col-sm-6 col-12">
                            <div class="form-group">
                                <label class="input-label" for="customer">{{translate('messages.customer')}}
                                    <span class="form-label-secondary text-danger"
                                          data-toggle="tooltip" data-placement="right"
                                          data-original-title="{{ translate('messages.Required.')}}"> *
                            </span>
                                </label>
                                <select id='customer' name="customer_id" data-placeholder="{{translate('messages.select_customer_by_name_or_phone')}}" class="js-data-example-ajax form-control" required>

                                </select>
                            </div>
                        </div>
                        <div class="col-sm-6 col-12">
                            <div class="form-group">
                                <label class="input-label" for="amount">{{translate("messages.amount")}} {{ \App\CentralLogics\Helpers::currency_symbol() }}
                                    <span class="form-label-secondary text-danger"
                                          data-toggle="tooltip" data-placement="right"
                                          data-original-title="{{ translate('messages.Required.')}}"> *
                            </span>
                                </label>
                                <input type="number" placeholder="{{translate('Ex: 50')}}" class="form-control" name="amount" min="0.01" id="amount" step=".01" required>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-group">
                                <label class="input-label" for="referance">{{translate('messages.reference')}} <small>({{translate('messages.optional')}})</small></label>

                                <input type="text" placeholder="{{ translate('Ex: 123') }}" class="form-control" name="referance" id="referance">
                            </div>
                        </div>
                    </div>
                    <div class="btn--container justify-content-end">
                        <button type="reset" id="reset" class="btn btn--reset">{{translate('messages.reset')}}</button>
                        <button type="submit" id="submit" class="btn btn--danger">{{translate('messages.deduct')}}</button>
                    </div>
                </form>
            </div>
            <!-- End Table -->
        </div>
    </div>

@endsection

@push('script_2')
    <script>
        $(document).on('ready', function () {
            // INITIALIZATION OF DATATABLES
            // =======================================================
            var datatable = $.HSCore.components.HSDatatables.init($('#columnSearchDatatable'));

            $('#column1_search').on('keyup', function () {
                datatable
                    .columns(1)
                    .search(this.value)
                    .draw();
            });


            $('#column3_search').on('change', function () {
                datatable
                    .columns(2)
                    .search(this.value)
                    .draw();
            });


            // INITIALIZATION OF SELECT2
            // =======================================================
            $('.js-select2-custom').each(function () {
                var select2 = $.HSCore.components.HSSelect2.init($(this));
            });
        });
    </script>

    <script>
        $('#deduct_fund').on('submit', function (e) {
            e.preventDefault();
            var formData = new FormData(this);

            // 验证表单
            if (!$('#customer').val()) {
                toastr.error('{{translate("messages.please_select_customer")}}', {
                    CloseButton: true,
                    ProgressBar: true
                });
                return;
            }

            if (!$('#amount').val() || $('#amount').val() <= 0) {
                toastr.error('{{translate("messages.please_enter_valid_amount")}}', {
                    CloseButton: true,
                    ProgressBar: true
                });
                return;
            }

            Swal.fire({
                title: '{{translate('messages.are_you_sure')}}',
                text: '{{translate('messages.you_want_to_deduct_fund')}} '+$('#amount').val()+' {{\App\CentralLogics\Helpers::currency_code().' '.translate('messages.from')}} '+$('#customer option:selected').text()+'{{translate('messages.wallet')}}',
                type: 'warning',
                showCancelButton: true,
                cancelButtonColor: 'default',
                confirmButtonColor: '#d33',
                cancelButtonText: '{{translate('messages.no')}}',
                confirmButtonText: '{{translate('messages.deduct_fund')}}',
                reverseButtons: true
            }).then((result) => {
                if (result.value) {
                    $.ajaxSetup({
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        }
                    });
                    $.post({
                        url: '{{route('admin.users.customer.wallet.deduct-fund')}}',
                        data: formData,
                        cache: false,
                        contentType: false,
                        processData: false,
                        beforeSend: function() {
                            $('#loading').show();
                            $('#submit').prop('disabled', true).text('{{translate("messages.processing")}}...');
                        },
                        success: function (data) {
                            $('#loading').hide();
                            $('#submit').prop('disabled', false).text('{{translate("messages.deduct")}}');
                            
                            // 检查是否有验证错误
                            if (data.errors && data.errors.length > 0) {
                                for (var i = 0; i < data.errors.length; i++) {
                                    toastr.error(data.errors[i].message, {
                                        CloseButton: true,
                                        ProgressBar: true
                                    });
                                }
                            } 
                            // 检查是否有单个错误消息
                            else if (data.errors && data.errors.message) {
                                toastr.error(data.errors.message, {
                                    CloseButton: true,
                                    ProgressBar: true
                                });
                            }
                            // 成功情况
                            else {
                                toastr.success('{{translate("messages.fund_deducted_successfully")}}', {
                                    CloseButton: true,
                                    ProgressBar: true
                                });
                                setTimeout(function () {
                                    window.location.reload();
                                }, 2000);
                            }
                        },
                        error: function (xhr) {
                            $('#loading').hide();
                            $('#submit').prop('disabled', false).text('{{translate("messages.deduct")}}');
                            
                            console.log('XHR Error:', xhr); // 调试用
                            
                            // 处理HTTP错误状态码
                            if (xhr.status === 422) {
                                // 验证错误
                                if (xhr.responseJSON && xhr.responseJSON.errors) {
                                    var errors = xhr.responseJSON.errors;
                                    if (Array.isArray(errors)) {
                                        for (var i = 0; i < errors.length; i++) {
                                            toastr.error(errors[i].message || errors[i], {
                                                CloseButton: true,
                                                ProgressBar: true
                                            });
                                        }
                                    } else {
                                        // 如果errors是对象格式
                                        Object.keys(errors).forEach(function(key) {
                                            if (Array.isArray(errors[key])) {
                                                errors[key].forEach(function(message) {
                                                    toastr.error(message, {
                                                        CloseButton: true,
                                                        ProgressBar: true
                                                    });
                                                });
                                            } else {
                                                toastr.error(errors[key], {
                                                    CloseButton: true,
                                                    ProgressBar: true
                                                });
                                            }
                                        });
                                    }
                                }
                            } else if (xhr.status === 200 && xhr.responseJSON && xhr.responseJSON.errors) {
                                // 业务逻辑错误（返回200但包含错误信息）
                                if (Array.isArray(xhr.responseJSON.errors)) {
                                    for (var i = 0; i < xhr.responseJSON.errors.length; i++) {
                                        toastr.error(xhr.responseJSON.errors[i].message, {
                                            CloseButton: true,
                                            ProgressBar: true
                                        });
                                    }
                                } else if (xhr.responseJSON.errors.message) {
                                    toastr.error(xhr.responseJSON.errors.message, {
                                        CloseButton: true,
                                        ProgressBar: true
                                    });
                                }
                            } else {
                                // 其他错误
                                var errorMessage = '{{translate("messages.something_went_wrong")}}';
                                if (xhr.responseJSON && xhr.responseJSON.message) {
                                    errorMessage = xhr.responseJSON.message;
                                } else if (xhr.responseText) {
                                    try {
                                        var response = JSON.parse(xhr.responseText);
                                        if (response.message) {
                                            errorMessage = response.message;
                                        }
                                    } catch (e) {
                                        // 忽略JSON解析错误，使用默认错误消息
                                    }
                                }
                                
                                toastr.error(errorMessage, {
                                    CloseButton: true,
                                    ProgressBar: true
                                });
                            }
                        }
                    });
                }
            })
        })

        $('.js-data-example-ajax').select2({
            ajax: {
                url: '{{route('admin.users.customer.select-list')}}',
                data: function (params) {
                    return {
                        q: params.term, // search term
                        page: params.page
                    };
                },
                processResults: function (data) {
                    return {
                    results: data
                    };
                },
                __port: function (params, success, failure) {
                    var $request = $.ajax(params);

                    $request.then(success);
                    $request.fail(failure);

                    return $request;
                }
            }
        });
    </script>
@endpush