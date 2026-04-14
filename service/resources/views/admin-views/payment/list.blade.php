@extends('layouts.admin.app')

@section('title', translate('messages.Payment List'))

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col-xl-10 col-md-9 col-sm-8 mb-3 mb-sm-0">
                    <h1 class="page-header-title text-capitalize m-0">
                        <span class="page-header-icon">
                            <img src="{{asset('public/assets/admin/img/payment.png')}}" class="w--26" alt="">
                        </span>
                        <span>
                            {{ translate('messages.Payment List') }}
                            <span class="badge badge-soft-dark ml-2">{{ $payments->total() }}</span>
                        </span>
                    </h1>
                </div>
            </div>
        </div>
        <!-- End Page Header -->

        <!-- Card -->
        <div class="card">
            <!-- Header -->
            <div class="card-header py-1 border-0">
                <div class="search--button-wrapper justify-content-end">
                    <form class="search-form min--260">
                        <!-- Search -->
                        <div class="input-group input--group">
                            <input id="datatableSearch_" type="search" name="search" class="form-control h--40px"
                                   placeholder="{{ translate('messages.Ex:') }} {{ translate('messages.search_by_id_transaction_id') }}" 
                                   value="{{ request()?->search ?? null}}" 
                                   aria-label="{{translate('messages.search')}}">
                            <button type="submit" class="btn btn--secondary"><i class="tio-search"></i></button>
                        </div>
                        <!-- End Search -->
                    </form>

                    @if(request()->get('search'))
                        <button type="reset" class="btn btn--primary ml-2 location-reload-to-base" 
                                data-url="{{url()->full()}}">{{translate('messages.reset')}}</button>
                    @endif

                    <!-- Export -->
                    <div class="hs-unfold mr-2">
                        <a class="js-hs-unfold-invoker btn btn-sm btn-white dropdown-toggle h--40px" href="javascript:;"
                           data-hs-unfold-options='{
                                "target": "#usersExportDropdown",
                                "type": "css-animation"
                            }'>
                            <i class="tio-download-to mr-1"></i> {{translate('messages.export')}}
                        </a>

                        <div id="usersExportDropdown" class="hs-unfold-content dropdown-unfold dropdown-menu dropdown-menu-sm-right">
                            <span class="dropdown-header">{{translate('messages.download_options')}}</span>
                            <a id="export-excel" class="dropdown-item" href="javascript:;">
                                <img class="avatar avatar-xss avatar-4by3 mr-2"
                                     src="{{asset('public/assets/admin')}}/svg/components/excel.svg"
                                     alt="Image Description">
                                {{translate('messages.excel')}}
                            </a>
                            <a id="export-csv" class="dropdown-item" href="javascript:;">
                                <img class="avatar avatar-xss avatar-4by3 mr-2"
                                     src="{{asset('public/assets/admin')}}/svg/components/placeholder-csv-format.svg"
                                     alt="Image Description">
                                .{{translate('messages.csv')}}
                            </a>
                        </div>
                    </div>

                    <!-- Filter -->
                    <div class="hs-unfold mr-2">
                        <a class="js-hs-unfold-invoker btn btn-sm btn-white h--40px filter-button-show" href="javascript:;">
                            <i class="tio-filter-list mr-1"></i> {{ translate('messages.filter') }} 
                            <span class="badge badge-success badge-pill ml-1" id="filter_count"></span>
                        </a>
                    </div>

                    <!-- Columns -->
                    <div class="hs-unfold">
                        <a class="js-hs-unfold-invoker btn btn-sm btn-white h--40px" href="javascript:;"
                           data-hs-unfold-options='{
                                "target": "#showHideDropdown",
                                "type": "css-animation"
                            }'>
                            <i class="tio-table mr-1"></i> {{translate('messages.columns')}}
                        </a>

                        <div id="showHideDropdown"
                             class="hs-unfold-content dropdown-unfold dropdown-menu dropdown-menu-right dropdown-card min--240">
                            <div class="card card-sm">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <span class="mr-2">{{translate('messages.payment_date')}}</span>
                                        <label class="toggle-switch toggle-switch-sm" for="toggleColumn_date">
                                            <input type="checkbox" class="toggle-switch-input" id="toggleColumn_date" checked>
                                            <span class="toggle-switch-label">
                                                <span class="toggle-switch-indicator"></span>
                                            </span>
                                        </label>
                                    </div>

                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <span class="mr-2">{{translate('messages.transaction_id')}}</span>
                                        <label class="toggle-switch toggle-switch-sm" for="toggleColumn_transaction">
                                            <input type="checkbox" class="toggle-switch-input" id="toggleColumn_transaction" checked>
                                            <span class="toggle-switch-label">
                                                <span class="toggle-switch-indicator"></span>
                                            </span>
                                        </label>
                                    </div>

                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <span class="mr-2">{{translate('messages.payment_method')}}</span>
                                        <label class="toggle-switch toggle-switch-sm" for="toggleColumn_method">
                                            <input type="checkbox" class="toggle-switch-input" id="toggleColumn_method" checked>
                                            <span class="toggle-switch-label">
                                                <span class="toggle-switch-indicator"></span>
                                            </span>
                                        </label>
                                    </div>

                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <span class="mr-2">{{translate('messages.amount')}}</span>
                                        <label class="toggle-switch toggle-switch-sm" for="toggleColumn_amount">
                                            <input type="checkbox" class="toggle-switch-input" id="toggleColumn_amount" checked>
                                            <span class="toggle-switch-label">
                                                <span class="toggle-switch-indicator"></span>
                                            </span>
                                        </label>
                                    </div>

                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <span class="mr-2">{{translate('messages.status')}}</span>
                                        <label class="toggle-switch toggle-switch-sm" for="toggleColumn_status">
                                            <input type="checkbox" class="toggle-switch-input" id="toggleColumn_status" checked>
                                            <span class="toggle-switch-label">
                                                <span class="toggle-switch-indicator"></span>
                                            </span>
                                        </label>
                                    </div>

                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="mr-2">{{translate('messages.actions')}}</span>
                                        <label class="toggle-switch toggle-switch-sm" for="toggleColumn_actions">
                                            <input type="checkbox" class="toggle-switch-input" id="toggleColumn_actions" checked>
                                            <span class="toggle-switch-label">
                                                <span class="toggle-switch-indicator"></span>
                                            </span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- End Header -->

            <!-- Table -->
            <div class="table-responsive datatable-custom">
                <table id="datatable"
                       class="table table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table fz--14px"
                       data-hs-datatables-options='{
                         "columnDefs": [{
                            "targets": [0],
                            "orderable": false
                          }],
                         "order": [],
                         "info": {
                           "totalQty": "#datatableWithPaginationInfoTotalQty"
                         },
                         "search": "#datatableSearch",
                         "entries": "#datatableEntries",
                         "isResponsive": false,
                         "isShowPaging": false,
                         "paging": false
                       }'>
                    <thead class="thead-light">
                    <tr>
                        <th class="border-0">{{translate('messages.sl')}}</th>
                        <th class="table-column-pl-0 border-0">{{translate('messages.payment_id')}}</th>
                        <th class="border-0">{{translate('messages.payment_date')}}</th>
                        <th class="border-0">{{translate('messages.transaction_id')}}</th>
                        <th class="border-0">{{translate('messages.payment_method')}}</th>
                        <th class="border-0">{{translate('messages.attribute')}}</th>
                        <th class="border-0">{{translate('messages.payer_info')}}</th>
                        <th class="border-0">{{translate('messages.amount')}}</th>
                        <th class="text-center border-0">{{translate('messages.status')}}</th>
                    </tr>
                    </thead>

                    <tbody id="set-rows">
                    @foreach($payments as $key => $payment)
                        <tr>
                            <td>{{ $key + $payments->firstItem() }}</td>
                            <td class="table-column-pl-0">
                                {{ $payment->id }}
                            </td>
                            <td>
                                <div>
                                    <div>
                                        {{ \App\CentralLogics\Helpers::date_format($payment->created_at) }}
                                    </div>
                                    <div class="d-block text-uppercase">
                                        {{ \App\CentralLogics\Helpers::time_format($payment->created_at) }}
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="d-block font-size-sm text-body">
                                    {{ $payment->transaction_id ?? translate('messages.N/A') }}
                                </span>
                            </td>
                            <td>
                                <span class="badge badge-soft-info">
                                    {{ translate(str_replace('_', ' ', $payment->payment_method)) }}
                                </span>
                            </td>
                            <td>
                                <span class="badge badge-soft-secondary">
                                    {{ translate(str_replace('_', ' ', $payment->attribute)) }}
                                </span>
                                @if($payment->attribute_id)
                                    <div class="font-size-sm text-body">
                                        ID: {{ $payment->attribute_id }}
                                    </div>
                                @endif
                            </td>
                            <td>
                                <div class="font-size-sm">
                                    <strong>{{ translate('messages.payer') }}:</strong> {{ $payment->payer_id }}<br>
                                    @if($payment->receiver_id)
                                        <strong>{{ translate('messages.receiver') }}:</strong> {{ $payment->receiver_id }}
                                    @endif
                                </div>
                            </td>
                            <td>
                                <div class="text-right mw--85px">
                                    {{ \App\CentralLogics\Helpers::format_currency($payment->payment_amount) }}
                                </div>
                            </td>
                            <td class="text-center">
                                @if($payment->is_paid)
                                    <span class="badge badge-soft-success">
                                        {{ translate('messages.paid') }}
                                    </span>
                                @else
                                    <span class="badge badge-soft-danger">
                                        {{ translate('messages.unpaid') }}
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <!-- End Table -->

            @if(count($payments) !== 0)
                <hr>
            @endif
            <div class="page-area">
                {!! $payments->appends($_GET)->links() !!}
            </div>
            @if(count($payments) === 0)
                <div class="empty--data">
                    <img src="{{asset('/public/assets/admin/svg/illustrations/sorry.svg')}}" alt="public">
                    <h5>{{ translate('no_data_found') }}</h5>
                </div>
            @endif
        </div>
        <!-- End Card -->

        <!-- Payment Filter Modal -->
        <div id="datatableFilterSidebar" class="hs-unfold-content_ sidebar sidebar-bordered sidebar-box-shadow initial-hidden">
            <div class="card card-lg sidebar-card sidebar-footer-fixed">
                <div class="card-header">
                    <h4 class="card-header-title">{{ translate('messages.payment_filter') }}</h4>
                    <a class="js-hs-unfold-invoker_ btn btn-icon btn-sm btn-ghost-dark ml-2 filter-button-hide" href="javascript:;">
                        <i class="tio-clear tio-lg"></i>
                    </a>
                </div>

                <form class="card-body sidebar-body sidebar-scrollbar" 
                      action="{{ route('admin.payment.filter') }}" method="POST" id="payment_filter_form">
                    @csrf
                    
                    <small class="text-cap mb-3">{{ translate('messages.payment_status') }}</small>
                    <div class="custom-control custom-radio mb-2">
                        <input type="radio" id="all_status" name="payment_status" class="custom-control-input" value="">
                        <label class="custom-control-label" for="all_status">{{ translate('messages.all') }}</label>
                    </div>
                    <div class="custom-control custom-radio mb-2">
                        <input type="radio" id="paid_status" name="payment_status" class="custom-control-input" value="1">
                        <label class="custom-control-label" for="paid_status">{{ translate('messages.paid') }}</label>
                    </div>
                    <div class="custom-control custom-radio mb-2">
                        <input type="radio" id="unpaid_status" name="payment_status" class="custom-control-input" value="0">
                        <label class="custom-control-label" for="unpaid_status">{{ translate('messages.unpaid') }}</label>
                    </div>

                    <hr class="my-4">

                    <small class="text-cap mb-3">{{ translate('messages.payment_method') }}</small>
                    <div class="mb-2">
                        <select name="payment_method" class="form-control js-select2-custom">
                            <option value="">{{ translate('messages.all') }}</option>
                            @foreach($payment_methods as $method)
                                <option value="{{ $method }}">{{ translate(str_replace('_', ' ', $method)) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <hr class="my-4">

                    <small class="text-cap mb-3">{{ translate('messages.attribute') }}</small>
                    <div class="mb-2">
                        <select name="attribute" class="form-control js-select2-custom">
                            <option value="">{{ translate('messages.all') }}</option>
                            @foreach($attributes as $attribute)
                                <option value="{{ $attribute }}">{{ translate(str_replace('_', ' ', $attribute)) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <hr class="my-4">

                    <small class="text-cap mb-3">{{ translate('messages.date_between') }}</small>
                    <div class="row">
                        <div class="col-12">
                            <div class="form-group m-0">
                                <input type="date" name="from_date" class="form-control" id="date_from" 
                                       value="{{ request()->from_date }}">
                            </div>
                        </div>
                        <div class="col-12 text-center">----{{ translate('messages.to') }}----</div>
                        <div class="col-12">
                            <div class="form-group">
                                <input type="date" name="to_date" class="form-control" id="date_to" 
                                       value="{{ request()->to_date }}">
                            </div>
                        </div>
                    </div>

                    <div class="card-footer sidebar-footer">
                        <div class="row gx-2">
                            <div class="col">
                                <button type="reset" class="btn btn-block btn-white" id="reset">
                                    {{ translate('Clear all filters') }}
                                </button>
                            </div>
                            <div class="col">
                                <button type="submit" class="btn btn-block btn-primary">
                                    {{ translate('messages.save') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <!-- End Payment Filter Modal -->
    </div>
@endsection

@push('script_2')
    <script>
        "use strict";
        $(document).on('ready', function () {
            // Initialize select2
            $('.js-select2-custom').each(function () {
                var select2 = $.HSCore.components.HSSelect2.init($(this));
            });

            // Initialize datatables
            let datatable = $.HSCore.components.HSDatatables.init($('#datatable'), {
                dom: 'Bfrtip',
                buttons: [
                    {
                        extend: 'copy',
                        className: 'd-none'
                    },
                    {
                        extend: 'excel',
                        className: 'd-none'
                    },
                    {
                        extend: 'csv',
                        className: 'd-none'
                    },
                    {
                        extend: 'print',
                        className: 'd-none'
                    },
                ],
                language: {
                    zeroRecords: '<div class="text-center p-4">' +
                        '<img class="w-7rem mb-3" src="{{asset('public/assets/admin')}}/svg/illustrations/sorry.svg" alt="Image Description">' +
                        '</div>'
                }
            });

            // Export buttons
            $('#export-excel').click(function () {
                datatable.button('.buttons-excel').trigger()
            });

            $('#export-csv').click(function () {
                datatable.button('.buttons-csv').trigger()
            });

            // Search functionality
            $('#datatableSearch').on('mouseup', function (e) {
                let $input = $(this),
                    oldValue = $input.val();

                if (oldValue == "") return;

                setTimeout(function () {
                    let newValue = $input.val();
                    if (newValue == "") {
                        datatable.search('').draw();
                    }
                }, 1);
            });

            // Toggle columns
            $('#toggleColumn_date').change(function (e) {
                datatable.columns(2).visible(e.target.checked)
            });

            $('#toggleColumn_transaction').change(function (e) {
                datatable.columns(3).visible(e.target.checked)
            });

            $('#toggleColumn_method').change(function (e) {
                datatable.columns(4).visible(e.target.checked)
            });

            $('#toggleColumn_amount').change(function (e) {
                datatable.columns(7).visible(e.target.checked)
            });

            $('#toggleColumn_status').change(function (e) {
                datatable.columns(8).visible(e.target.checked)
            });

            $('#toggleColumn_actions').change(function (e) {
                datatable.columns(9).visible(e.target.checked)
            });

            // Filter sidebar
            $('.filter-button-show').on('click', function() {
                $('#datatableFilterSidebar').removeClass('initial-hidden');
            });

            $('.filter-button-hide').on('click', function() {
                $('#datatableFilterSidebar').addClass('initial-hidden');
            });

            // Reset filters
            $('#reset').on('click', function() {
                location.href = '{{ route('admin.payment.filter_reset') }}';
            });
        });
    </script>
@endpush