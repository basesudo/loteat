@extends('layouts.admin.app')

@section('title', translate('messages.Payment_details'))

@push('css_or_js')
<style>
    .payment-info-item {
        margin-bottom: 15px;
    }
    .payment-info-item p {
        margin-bottom: 5px;
    }
    .image-container {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 10px;
    }
    .payment-image {
        width: 120px;
        height: 120px;
        object-fit: cover;
        border-radius: 5px;
        border: 1px solid #e3e9ef;
        cursor: pointer;
        transition: transform 0.2s;
    }
    .payment-image:hover {
        transform: scale(1.05);
    }
    .table-image-container {
        display: flex;
        flex-wrap: wrap;
        gap: 5px;
        max-width: 200px;
    }
    .table-payment-image {
        width: 40px;
        height: 40px;
        object-fit: cover;
        border-radius: 4px;
        border: 1px solid #e3e9ef;
        cursor: pointer;
    }
    /* 图片查看器样式 */
    .image-viewer-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.9);
        display: none;
        justify-content: center;
        align-items: center;
        z-index: 9999;
        cursor: zoom-out;
    }
    .image-viewer-container {
        position: relative;
        max-width: 90%;
        max-height: 90%;
    }
    .image-viewer-img {
        max-width: 100%;
        max-height: 90vh;
        object-fit: contain;
    }
    .image-viewer-close {
        position: absolute;
        top: -40px;
        right: 0;
        color: #fff;
        font-size: 30px;
        cursor: pointer;
    }
    .image-viewer-nav {
        position: absolute;
        top: 50%;
        width: 50px;
        height: 50px;
        background: rgba(255,255,255,0.2);
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        cursor: pointer;
        font-size: 24px;
        transform: translateY(-50%);
    }
    .image-viewer-prev {
        left: -60px;
    }
    .image-viewer-next {
        right: -60px;
    }
</style>
@endpush

@section('content')
<div class="content container-fluid">
    <!-- Page Header -->
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col-sm mb-2 mb-sm-0">
                <h1 class="page-header-title">
                    <i class="tio-wallet"></i> {{ translate('messages.Payment_details') }}
                    <span class="badge badge-soft-dark ml-2">{{ translate('messages.Agency ID') }}: {{ $agency_id }}</span>
                </h1>
            </div>
            <div class="col-sm-auto">
                <a class="btn btn-primary" href="{{ url()->previous() }}">
                    <i class="tio-back-ui"></i> {{ translate('messages.back') }}
                </a>
            </div>
        </div>
    </div>
    <!-- End Page Header -->

    <!-- Card -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-header-title">{{ translate('messages.Payment_details') }}</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                    <thead class="thead-light">
                        <tr>
                            <th>{{ translate('messages.date') }}</th>
                            <th>{{ translate('messages.title') }}</th>
                            <th>{{ translate('messages.content') }}</th>
                            <th>{{ translate('messages.images') }}</th>
                            <!-- 移除 action 列的表头 -->
                        </tr>
                    </thead>
                    <tbody>
                    @if(count($agency_payments) > 0)
                        @foreach($agency_payments as $key => $payment)
                            <tr>
                                <td>
                                    <div>
                                        {{ \App\CentralLogics\Helpers::date_format($payment->created_at) }}
                                    </div>
                                    <div class="d-block text-uppercase">
                                        {{ \App\CentralLogics\Helpers::time_format($payment->created_at) }}
                                    </div>
                                </td>
                                <td>
                                    <span class="d-block text-body">
                                    {{ $payment->title ?? translate('messages.N/A') }}
                                    </span>
                                </td>
                                <td>
                                    <span class="d-block text-body">
                                    {{ $payment->content ?? translate('messages.N/A') }}
                                    </span>
                                </td>
                                <td>
                                    @if($payment->images)
                                        @php
                                            $images = json_decode($payment->images, true);
                                        @endphp
                                        @if(count($images) > 0)
                                            <div class="table-image-container" data-payment-id="{{ $payment->id }}">
                                                @foreach($images as $index => $image)
                                                    <img src="{{asset('storage/app/public/agency_payment/'.$image)}}" 
                                                         alt="{{translate('payment_image')}}" 
                                                         class="table-payment-image payment-thumbnail"
                                                         data-image-index="{{ $index }}"
                                                         data-payment-id="{{ $payment->id }}">
                                                    @if($index >= 4 && count($images) > 5)
                                                        <div class="table-payment-image d-flex align-items-center justify-content-center bg-secondary text-white">
                                                            +{{ count($images) - 5 }}
                                                        </div>
                                                        @break
                                                    @endif
                                                @endforeach
                                            </div>
                                        @else
                                            <span class="badge badge-soft-warning">{{ translate('messages.No Image') }}</span>
                                        @endif
                                    @else
                                        <span class="badge badge-soft-warning">{{ translate('messages.No Image') }}</span>
                                    @endif
                                </td>
                                
                                <!-- 移除 action 列的数据单元格 -->
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="4" class="text-center">{{ translate('messages.No Records') }}</td>
                        </tr>
                    @endif
                    </tbody>
                </table>
                <!-- End Table -->
                
                @if(count($agency_payments) === 0)
                <div class="empty--data">
                    <img src="{{ asset('public/assets/admin/svg/illustrations/sorry.svg') }}" alt="public">
                    <h5>
                        {{ translate('no_data_found') }}
                    </h5>
                </div>
                @endif
            </div>
            
            <!-- Pagination -->
            <div class="page-area px-4 pb-3">
                <div class="d-flex align-items-center justify-content-end">
                    {{ $agency_payments->links() }}
                </div>
            </div>
            <!-- End Pagination -->
        </div>
        <!-- End Card -->
    </div>
</div>

<!-- 图片查看器 -->
<div class="image-viewer-overlay" id="image-viewer">
    <div class="image-viewer-container">
        <span class="image-viewer-close">&times;</span>
        <img src="" class="image-viewer-img" id="expanded-image">
        <div class="image-viewer-nav image-viewer-prev">&#10094;</div>
        <div class="image-viewer-nav image-viewer-next">&#10095;</div>
    </div>
</div>
@endsection

@push('script_2')
<script>
    // 全局存储当前查看的图片信息
    const viewerState = {
        currentPaymentId: null,
        currentIndex: 0,
        images: []
    };

    $(document).ready(function() {
        // 初始化点击事件
        $('.payment-thumbnail').on('click', function() {
            const paymentId = $(this).data('payment-id');
            const imageIndex = parseInt($(this).data('image-index')) || 0;
            openImageViewer(paymentId, imageIndex);
        });

        // 关闭图片查看器
        $('.image-viewer-close, .image-viewer-overlay').on('click', function() {
            $('#image-viewer').fadeOut();
        });

        // 防止点击图片本身关闭查看器
        $('.image-viewer-container').on('click', function(e) {
            e.stopPropagation();
        });

        // 上一张/下一张导航
        $('.image-viewer-prev').on('click', function(e) {
            e.stopPropagation();
            navigateImage(-1);
        });

        $('.image-viewer-next').on('click', function(e) {
            e.stopPropagation();
            navigateImage(1);
        });

        // 键盘导航
        $(document).keydown(function(e) {
            if ($('#image-viewer').is(':visible')) {
                if (e.keyCode === 37) { // 左箭头
                    navigateImage(-1);
                } else if (e.keyCode === 39) { // 右箭头
                    navigateImage(1);
                } else if (e.keyCode === 27) { // ESC键
                    $('#image-viewer').fadeOut();
                }
            }
        });
    });

    function openImageViewer(paymentId, imageIndex) {
        // 直接从当前DOM获取图片信息
        const paymentContainer = $(`.table-image-container[data-payment-id="${paymentId}"]`);
        if (paymentContainer.length === 0) return;
        
        // 收集所有图片路径
        const images = [];
        paymentContainer.find('img.payment-thumbnail').each(function() {
            const src = $(this).attr('src');
            if (src) {
                // 提取文件名部分
                const filename = src.substring(src.lastIndexOf('/') + 1);
                images.push(filename);
            }
        });
        
        if (images.length > 0) {
            // 更新查看器状态
            viewerState.currentPaymentId = paymentId;
            viewerState.currentIndex = imageIndex < images.length ? imageIndex : 0;
            viewerState.images = images;
            
            // 设置图片
            updateViewerImage();
            
            // 显示查看器
            $('#image-viewer').css('display', 'flex');
        } else {
            console.error('未找到图片');
        }
    }

    function updateViewerImage() {
        if (!viewerState.images || viewerState.images.length === 0) return;
        
        const baseUrl = '{{ asset("storage/app/public/agency_payment") }}/';
        const filename = viewerState.images[viewerState.currentIndex];
        const imagePath = baseUrl + filename;
        
        $('#expanded-image').attr('src', imagePath);
        
        // 控制导航按钮的显示/隐藏
        if (viewerState.images.length <= 1) {
            $('.image-viewer-nav').hide();
        } else {
            $('.image-viewer-nav').show();
        }
    }

    function navigateImage(direction) {
        if (!viewerState.images || viewerState.images.length === 0) return;
        
        viewerState.currentIndex = (viewerState.currentIndex + direction + viewerState.images.length) % viewerState.images.length;
        updateViewerImage();
    }
</script>
@endpush