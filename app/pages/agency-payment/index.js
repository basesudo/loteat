import React, { useState, useEffect } from "react";
import { useRouter } from "next/router";
import { useTranslation } from "react-i18next";
import { useSelector } from "react-redux";
import { toast } from "react-hot-toast";
import axios from "axios";

// MUI 组件
import CssBaseline from "@mui/material/CssBaseline";
import {
  Box, Card, Typography, TextField, Button, Grid,
  Stack, Divider, FormControlLabel, Checkbox, Link,
  Alert, Chip, Avatar, Paper, Table, TableBody,
  TableCell, TableContainer, TableHead, TableRow,
  CircularProgress
} from "@mui/material";

// 自定义组件
import MainLayout from "../../src/components/layout/MainLayout";
import AuthGuard from "../../src/components/route-guard/AuthGuard";
import SEO from "../../src/components/seo";
import CustomContainer from "../../src/components/container";
import PaymentMethodCard from "../../src/components/checkout/PaymentMethodCard";
import { CustomStackFullWidth } from "../../src/styled-components/CustomStyles.style";

// API 钩子
import useGetAgencyPaymentShare from "../../src/api-manage/hooks/react-query/agency-payment/useGetAgencyPaymentShareTpl";
import useGetAgencyPaymentThankYou from "../../src/api-manage/hooks/react-query/agency-payment/useGetAgencyPaymentThankYouTpl";
import useGetAgencyPaymentInfo from "../../src/api-manage/hooks/react-query/agency-payment/useGetAgencyPaymentInfo";
import MainApi from "../../src/api-manage/MainApi";
import { baseUrl } from "../../src/api-manage/MainApi";

// 服务端属性
import { getServerSideProps } from "../index";

// 安全的错误处理器 - 确保不会在非数组上调用数组方法
const safelyHandleErrors = (error) => {
  if (!error) return;
  
  // 安全地访问错误响应数据
  const errorData = error.response?.data;
  if (!errorData) return;
  
  // 处理错误数组，确保它确实是数组
  const errors = errorData.errors;
  if (errors) {
    if (Array.isArray(errors)) {
      // 现在我们确定它是数组，可以安全地使用forEach
      errors.forEach(item => {
        if (typeof handleTokenExpire === 'function') {
          handleTokenExpire(item);
        }
      });
    } else if (typeof errors === 'object') {
      // 如果errors是对象而不是数组，单独处理
      if (typeof handleTokenExpire === 'function') {
        handleTokenExpire(errors);
      }
    }
  } else if (errorData.message) {
    // 如果没有errors数组但有message
    console.error("API Error:", errorData.message);
  }
};

const AgencyPaymentPage = ({ configData, landingPageData }) => {
  const router = useRouter();
  const { t } = useTranslation();
  const { agency_id, status } = router.query;

  // 参数错误状态
  const [paramError, setParamError] = useState(false);
  // 标准化的AgencyId（方便在代码中一致使用）
  const [agencyId, setAgencyId] = useState("");
  // 支付状态
  const [paymentStatus, setPaymentStatus] = useState(null);

  // 支付和同意状态
  const [paymentMethod, setPaymentMethod] = useState(null);
  const [agreedToTerms, setAgreedToTerms] = useState(false);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [isCheckedOffline, setIsCheckedOffline] = useState(false);
  // 模板状态
  const [customShareText, setCustomShareText] = useState("");
  const [thankYouTemplate, setThankYouTemplate] = useState("");
  // 重定向状态
  const [isRedirecting, setIsRedirecting] = useState(false);
  // 订单是否存在
  const [orderNotFound, setOrderNotFound] = useState(false);
  // 是否初始化完成
  const [isInitialized, setIsInitialized] = useState(false);

  // 首先验证并设置Agency ID和支付状态
  useEffect(() => {
    if (!agency_id) {
      setParamError(true);
      setAgencyId("");
    } else {
      setParamError(false);
      setAgencyId(agency_id);
    }
  }, [agency_id]);

  // 获取订单信息
  const {
    data: orderInfoData,
    isLoading: orderInfoLoading,
    error: orderInfoError
  } = useGetAgencyPaymentInfo(agencyId || null);

  // 获取分享和感谢模板
  const {
    data: shareData,
    isLoading: shareLoading,
    error: shareError
  } = useGetAgencyPaymentShare(agencyId || null);

  const {
    data: thankYouData,
    isLoading: thankYouLoading,
    error: thankYouError
  } = useGetAgencyPaymentThankYou(agencyId || null);

  // 判断是否所有API请求都已完成(无论成功或失败)
  const allRequestsCompleted = !orderInfoLoading && !shareLoading && !thankYouLoading;

  // 处理API初始化完成状态
  useEffect(() => {
    if (allRequestsCompleted) {
      setIsInitialized(true);
    }
  }, [allRequestsCompleted]);

  // 处理API错误
  useEffect(() => {
    // 安全处理订单信息错误
    if (orderInfoError) {
      safelyHandleErrors(orderInfoError);
      
      // 检查是否是订单不存在的错误
      const errorMessage = orderInfoError.response?.data?.message;
      if (errorMessage === "Order not found") {
        setOrderNotFound(true);
      }
    }
    
    // 安全处理分享模板错误
    if (shareError) {
      safelyHandleErrors(shareError);
    }
    
    // 安全处理感谢模板错误
    if (thankYouError) {
      safelyHandleErrors(thankYouError);
    }
  }, [orderInfoError, shareError, thankYouError]);

  // 处理订单不存在的情况
  useEffect(() => {
    // 检查订单API响应
    if (orderInfoData && orderInfoData.success === false && orderInfoData.message === "Order not found") {
      setOrderNotFound(true);
    }
  }, [orderInfoData]);

  // 根据订单状态处理支付状态
  useEffect(() => {
    const orderInfo = orderInfoData?.order || null;
    
    // 只有在订单未支付的情况下，才考虑URL中的status参数
    if (orderInfo && orderInfo.payment_status === "paid") {
      // 订单已支付，忽略URL中的status参数
      setPaymentStatus("paid");
    } else if (status === "success" || status === "fail") {
      // 订单未支付，使用URL中的status参数
      setPaymentStatus(status);
    } else {
      // 没有status参数且订单未支付，设置为null
      setPaymentStatus(null);
    }
  }, [orderInfoData, status]);

  // 安全处理错误对象 - 不假设错误是数组
  const handleSafeError = (error) => {
    if (!error) return null;
    
    // 如果错误对象有errors属性
    if (error.errors) {
      // 如果errors是数组，返回第一个错误
      if (Array.isArray(error.errors)) {
        return error.errors[0]?.message || "Unknown error";
      }
      // 如果errors是对象，返回其message属性或整个对象
      return error.errors.message || JSON.stringify(error.errors);
    }
    
    // 直接返回错误消息或整个错误对象
    return error.message || JSON.stringify(error);
  };

  // 处理模板数据
  useEffect(() => {
    // 只有在有效的agencyId时才处理模板数据
    if (!agencyId) return;

    // 设置分享文本
    if (shareData && !shareData.errors) {
      const shareText = shareData?.data?.text || shareData?.text || shareData || "";
      setCustomShareText(shareText);
    }

    // 设置感谢模板
    if (thankYouData && !thankYouData.errors) {
      setThankYouTemplate(thankYouData);
    }

    // 处理分享模板错误 - 使用安全的错误处理
    if (shareError || (shareData && shareData.errors)) {
      const errorMessage = shareError ? handleSafeError(shareError) : handleSafeError(shareData);
      console.error("获取分享模板错误:", errorMessage);
      // 不设置orderNotFound，因为分享模板缺失可以正常显示页面
    }

    // 处理感谢模板错误 - 使用安全的错误处理
    if (thankYouError || (thankYouData && thankYouData.errors)) {
      const errorMessage = thankYouError ? handleSafeError(thankYouError) : handleSafeError(thankYouData);
      console.error("获取感谢模板错误:", errorMessage);
      // 不设置orderNotFound，因为感谢模板缺失可以正常显示页面
    }
  }, [shareData, thankYouData, shareError, thankYouError, agencyId]);

  // 表单提交处理
  const handleSubmit = async (e) => {
    e.preventDefault();

    // 验证条款同意
    if (!agreedToTerms) {
      toast.error(t("Please agree to the terms and conditions"));
      return;
    }

    // 验证支付方式
    if (!paymentMethod) {
      toast.error(t("Please select a payment method"));
      return;
    }

    setIsSubmitting(true);
    setIsRedirecting(true);
    try {
      // 获取当前页面URL作为callback (不包含状态参数)
      const currentUrl = window.location.href.split('?')[0] + `?agency_id=${agencyId}`;

      // 构建GET请求的查询参数
      const queryParams = `?agency_id=${agencyId}&payment_method=${paymentMethod}&callback=${encodeURIComponent(currentUrl)}`;

      const url = `${baseUrl}/payment-mobile${queryParams}`;
      router.push(url);

    } catch (error) {
      console.error("支付处理错误:", error);
      safelyHandleErrors(error);
      toast.error(error?.response?.data?.message || t("An error occurred while processing your payment"));
    } finally {
      setIsSubmitting(false);
    }
  };

  // 根据支付状态返回相应的Chip
  const renderPaymentStatusChip = (status) => {
    let color = "default";
    if (status === "paid") color = "success";
    else if (status === "unpaid") color = "warning";

    return (
      <Chip
        label={t(status.charAt(0).toUpperCase() + status.slice(1))}
        color={color}
        size="small"
        variant="outlined"
      />
    );
  };

  // 根据订单状态返回相应的Chip
  const renderOrderStatusChip = (status) => {
    let color = "default";
    if (status === "delivered") color = "success";
    else if (status === "pending") color = "warning";
    else if (status === "processing") color = "info";
    else if (status === "cancelled") color = "error";

    return (
      <Chip
        label={t(status.charAt(0).toUpperCase() + status.slice(1))}
        color={color}
        size="small"
      />
    );
  };

  // 获取订单信息
  const orderInfo = orderInfoData?.order || null;

  // 判断是否显示支付表单 (仅当订单未支付且没有成功的支付状态时显示)
  const showPaymentForm = orderInfo?.payment_status === "unpaid" && paymentStatus !== "success" && !orderNotFound;

  // 判断是否显示分享信息 (仅当订单未支付且没有成功的支付状态时显示)
  const showShareInfo = orderInfo?.payment_status === "unpaid" && paymentStatus !== "success" && !orderNotFound;

  // 判断是否显示已支付提示
  const showPaidInfo = orderInfo?.payment_status === "paid" && !orderNotFound;

  // 判断是否显示支付成功提示 (仅在刚完成支付时显示)
  const showSuccessInfo = paymentStatus === "success" && orderInfo?.payment_status === "unpaid" && !orderNotFound;

  // 判断是否显示加载状态 (任何API请求正在加载中)
  const showLoading = orderInfoLoading || shareLoading || thankYouLoading || !isInitialized;

  return (
    <>
      <CssBaseline />
      <SEO
        title={configData ? t("Agency Payment") : t("Loading...")}
        image={`${configData?.base_urls?.business_logo_url}/${configData?.fav_icon}`}
        businessName={configData?.business_name}
      />

      <MainLayout configData={configData} landingPageData={landingPageData}>
        <CustomContainer>
          <Box sx={{ py: 4 }}>
            <Card sx={{ p: 3, maxWidth: 800, mx: "auto" }}>
              {/* 主要显示逻辑 */}
              {showLoading ? (
                // 全页面加载状态
                <Box sx={{ p: 4, textAlign: 'center' }}>
                  <CircularProgress size={40} sx={{ mb: 2 }} />
                  <Typography>{t("Loading payment information...")}</Typography>
                </Box>
              ) : paramError ? (
                <Alert severity="error" sx={{ 
                  mb: 3,
                  '& .MuiAlert-message': {
                    width: '100%',
                  },
                  alignItems: 'center'
                }}>
                  {t("Missing required parameter: Agency ID. Please provide a valid Agency ID.")}
                </Alert>
              ) : orderNotFound ? (
                <Alert 
                  severity="error" 
                  sx={{ 
                    mb: 3,
                    '& .MuiAlert-message': {
                      width: '100%',
                    },
                    alignItems: 'center'
                  }}
                >
                  {t("Order not found. This order may have been removed or the ID is invalid.")}
                </Alert>
              ) : (
                <>
                  {/* 支付状态提示 - 只在订单未支付时显示URL状态 */}
                  {orderInfo?.payment_status === "unpaid" && paymentStatus === "fail" && (
                    <Alert
                      severity="error"
                      sx={{
                        mb: 3,
                        alignItems: 'center',
                        '& .MuiAlert-message': {
                          width: '100%',
                          textAlign: 'center'
                        }
                      }}
                    >
                      {t("Payment cancelled or failed. Please try again.")}
                    </Alert>
                  )}

                  {/* 支付成功提示 - 只在订单未支付且URL状态为success时显示 */}
                  {showSuccessInfo && (
                    <Box
                      sx={{
                        p: 2.5,
                        bgcolor: 'success.lighter',
                        borderRadius: 1,
                        border: '1px solid',
                        borderColor: 'success.light',
                        boxShadow: '0 1px 3px rgba(0,0,0,0.05)',
                        mb: 3
                      }}
                    >
                      <Typography variant="h5" color="success.main" gutterBottom>
                        {t("Payment Successful!")}
                      </Typography>
                      <Box
                        sx={{
                          '& a': { color: 'primary.main' },
                          '& img': { maxWidth: '100%', height: 'auto', borderRadius: '4px' }
                        }}
                      >
                        <Typography variant="body2" dangerouslySetInnerHTML={{ __html: thankYouTemplate || t("Thank you for your payment. Your order has been processed successfully.") }} />
                      </Box>
                    </Box>
                  )}

                  {/* 已支付提示 - 仅在订单已支付时显示 */}
                  {showPaidInfo && (
                    <Box
                      sx={{
                        p: 2.5,
                        bgcolor: 'success.lighter',
                        borderRadius: 1,
                        border: '1px solid',
                        borderColor: 'success.light',
                        boxShadow: '0 1px 3px rgba(0,0,0,0.05)',
                        mb: 3
                      }}
                    >
                      <Typography variant="h5" color="success.main" gutterBottom>
                        {t("Payment Already Completed")}
                      </Typography>
                      <Typography variant="body2">
                        {t("This order has already been paid. Thank you for your payment.")}
                      </Typography>
                    </Box>
                  )}

                  {/* 分享信息区域 - 仅在未支付时显示 */}
                  {showShareInfo && (
                    <Stack spacing={2} sx={{ mb: 3 }}>
                      {/* 分享信息区域 */}
                      {shareLoading ? (
                        <Box sx={{ p: 2, bgcolor: 'background.paper', borderRadius: 1, opacity: 0.7 }}>
                          <Typography variant="body2">{t("Loading share information...")}</Typography>
                        </Box>
                      ) : shareError || (shareData && shareData.errors) ? (
                        <Box sx={{ p: 2, bgcolor: 'background.paper', borderRadius: 1 }}>
                          <Typography variant="subtitle2" color="text.secondary">
                            {t("Share information is not available for this order.")}
                          </Typography>
                        </Box>
                      ) : customShareText ? (
                        <Box
                          sx={{
                            p: 2.5,
                            bgcolor: 'background.paper',
                            borderRadius: 1,
                            border: '1px solid',
                            borderColor: 'divider',
                            boxShadow: '0 1px 3px rgba(0,0,0,0.05)'
                          }}
                        >
                          <Typography variant="subtitle1" fontWeight="bold" gutterBottom color="primary.main">
                            {t("Share Information")}
                          </Typography>
                          <Box
                            sx={{
                              '& a': { color: 'primary.main' },
                              '& img': { maxWidth: '100%', height: 'auto', borderRadius: '4px' }
                            }}
                          >
                            <Typography variant="body2" dangerouslySetInnerHTML={{ __html: customShareText }} />
                          </Box>
                        </Box>
                      ) : null}
                    </Stack>
                  )}

                  <Divider sx={{ mb: 3 }} />

                  {/* 订单信息区域 */}
                  {orderInfo ? (
                    <Box sx={{ mb: 4 }}>
                      <Typography variant="h5" gutterBottom sx={{ mb: 2 }}>
                        {t("Order Information")} #{orderInfo.id}
                      </Typography>

                      {/* 订单状态和支付状态 */}
                      <Stack direction="row" spacing={2} sx={{ mb: 3 }}>
                        <Box>
                          <Typography variant="body2" color="text.secondary" gutterBottom>
                            {t("Created At")}
                          </Typography>
                          <Typography variant="body2">
                            {orderInfo.created_at}
                          </Typography>
                        </Box>
                        <Box>
                          <Typography variant="body2" color="text.secondary" gutterBottom>
                            {t("Status")}
                          </Typography>
                          {renderOrderStatusChip(orderInfo.order_status)}
                        </Box>
                        <Box>
                          <Typography variant="body2" color="text.secondary" gutterBottom>
                            {t("Payment Status")}
                          </Typography>
                          {renderPaymentStatusChip(orderInfo.payment_status)}
                        </Box>
                      </Stack>

                      {/* 客户信息 */}
                      <Paper sx={{ p: 2, mb: 3 }} variant="outlined">
                        <Typography variant="subtitle1" gutterBottom>
                          {t("Customer Information")}
                        </Typography>
                        <Grid container spacing={2}>
                          <Grid item xs={12} sm={4}>
                            <Typography variant="body2" color="text.secondary">
                              {t("Name")}
                            </Typography>
                            <Typography variant="body1">
                              {orderInfo.customer?.name}
                            </Typography>
                          </Grid>
                          <Grid item xs={12} sm={4}>
                            <Typography variant="body2" color="text.secondary">
                              {t("Phone")}
                            </Typography>
                            <Typography variant="body1">
                              {orderInfo.customer?.phone}
                            </Typography>
                          </Grid>
                          <Grid item xs={12} sm={4}>
                            <Typography variant="body2" color="text.secondary">
                              {t("Email")}
                            </Typography>
                            <Typography variant="body1">
                              {orderInfo.customer?.email || t("Not provided")}
                            </Typography>
                          </Grid>
                        </Grid>
                      </Paper>

                      {/* 订单项目 - 添加商品图片显示 */}
                      {orderInfo.items && orderInfo.items.length > 0 && (
                        <TableContainer component={Paper} variant="outlined" sx={{ mb: 3 }}>
                          <Table size="small">
                            <TableHead>
                              <TableRow>
                                <TableCell width="80px">{t("Image")}</TableCell>
                                <TableCell>{t("Item")}</TableCell>
                                <TableCell align="right">{t("Price")}</TableCell>
                                <TableCell align="right">{t("Quantity")}</TableCell>
                                <TableCell align="right">{t("Total")}</TableCell>
                              </TableRow>
                            </TableHead>
                            <TableBody>
                              {orderInfo.items.map((item, index) => (
                                <TableRow key={index}>
                                  <TableCell>
                                    <Box
                                      component="img"
                                      src={item.image || "/images/placeholder-image.png"}
                                      alt={item.name || `Item #${index + 1}`}
                                      sx={{
                                        width: 60,
                                        height: 60,
                                        objectFit: "cover",
                                        borderRadius: 1,
                                        border: '1px solid',
                                        borderColor: 'divider',
                                      }}
                                      onError={(e) => {
                                        e.target.src = "/images/placeholder-image.png";
                                      }}
                                    />
                                  </TableCell>
                                  <TableCell component="th" scope="row">
                                    {item.name || `Item #${index + 1}`}
                                  </TableCell>
                                  <TableCell align="right">
                                    {configData?.currency_symbol}{item.price?.toFixed(2) || "0.00"}
                                  </TableCell>
                                  <TableCell align="right">
                                    {item.quantity || 1}
                                  </TableCell>
                                  <TableCell align="right">
                                    {configData?.currency_symbol}{(item.price * (item.quantity || 1)).toFixed(2) || "0.00"}
                                  </TableCell>
                                </TableRow>
                              ))}
                            </TableBody>
                          </Table>
                        </TableContainer>
                      )}

                      {/* 价格摘要 */}
                      <Paper sx={{ p: 2, mb: 3 }} variant="outlined">
                        <Typography variant="subtitle1" gutterBottom>
                          {t("Payment Summary")}
                        </Typography>
                        <Stack spacing={1}>
                          <Stack direction="row" justifyContent="space-between">
                            <Typography variant="body2">{t("Subtotal")}</Typography>
                            <Typography variant="body2">
                              {configData?.currency_symbol}{(orderInfo.order_amount
                                - orderInfo.delivery_charge
                                + orderInfo.store_discount_amount
                                + orderInfo.coupon_discount_amount
                                - orderInfo.total_tax_amount).toFixed(2)}
                            </Typography>
                          </Stack>
                          {orderInfo.total_tax_amount > 0 && (
                            <Stack direction="row" justifyContent="space-between">
                              <Typography variant="body2">{t("Tax")}</Typography>
                              <Typography variant="body2">
                                {configData?.currency_symbol}{orderInfo.total_tax_amount.toFixed(2)}
                              </Typography>
                            </Stack>
                          )}
                          {orderInfo.delivery_charge > 0 && (
                            <Stack direction="row" justifyContent="space-between">
                              <Typography variant="body2">{t("Delivery Fee")}</Typography>
                              <Typography variant="body2">
                                {configData?.currency_symbol}{orderInfo.delivery_charge.toFixed(2)}
                              </Typography>
                            </Stack>
                          )}
                          {orderInfo.coupon_discount_amount > 0 && (
                            <Stack direction="row" justifyContent="space-between">
                              <Typography variant="body2">{t("Coupon Discount")}</Typography>
                              <Typography variant="body2" color="success.main">
                                -{configData?.currency_symbol}{orderInfo.coupon_discount_amount.toFixed(2)}
                              </Typography>
                            </Stack>
                          )}
                          {orderInfo.store_discount_amount > 0 && (
                            <Stack direction="row" justifyContent="space-between">
                              <Typography variant="body2">{t("Store Discount")}</Typography>
                              <Typography variant="body2" color="success.main">
                                -{configData?.currency_symbol}{orderInfo.store_discount_amount.toFixed(2)}
                              </Typography>
                            </Stack>
                          )}
                          <Divider />
                          <Stack direction="row" justifyContent="space-between">
                            <Typography variant="subtitle1">{t("Total Amount")}</Typography>
                            <Typography variant="subtitle1" fontWeight="bold">
                              {configData?.currency_symbol}{orderInfo.order_amount.toFixed(2)}
                            </Typography>
                          </Stack>
                        </Stack>
                      </Paper>

                      {/* 支付表单区域 - 仅在未支付时显示 */}
                      {showPaymentForm && (
                        <Box>
                          <Typography variant="h6" gutterBottom>
                            {t("Choose Payment Method")}
                          </Typography>
                          <Box sx={{ width: '100%', mb: 2 }}>
                            {/* 确保Grid容器使用全宽 */}
                            <Grid container spacing={2} sx={{ width: '100%', m: 0 }}>
                              {configData?.active_payment_method_list?.map((item, index) => (
                                <Grid
                                  item
                                  xs={6}
                                  key={index}
                                >
                                  <PaymentMethodCard
                                    parcel={"false"}
                                    paymentType={item?.gateway_title}
                                    image={item?.gateway_image}
                                    paymentMethod={paymentMethod}
                                    setPaymentMethod={setPaymentMethod}
                                    setIsCheckedOffline={setIsCheckedOffline}
                                    type={item?.gateway}
                                    imageUrl={configData?.base_urls?.gateway_image_url}
                                    digitalPaymentMethodActive={configData?.digital_payment_info?.digital_payment}
                                  />
                                </Grid>
                              ))}
                            </Grid>
                          </Box>

                          <Grid item xs={12} sx={{ mt: 2 }}>
                            <FormControlLabel
                              control={
                                <Checkbox
                                  checked={agreedToTerms}
                                  onChange={(e) => setAgreedToTerms(e.target.checked)}
                                  color="primary"
                                />
                              }
                              label={
                                <Typography variant="body2">
                                  {t("I agree to the")}
                                  <Link href="/terms-and-conditions" target="_blank" sx={{ mx: 0.5 }}>
                                    {t("Terms of Service")}
                                  </Link>
                                  {t("and")}
                                  <Link href="/refund-policy" target="_blank" sx={{ ml: 0.5 }}>
                                    {t("Refund Policy")}
                                  </Link>
                                </Typography>
                              }
                            />
                          </Grid>

                          <Button
                            type="submit"
                            variant="contained"
                            fullWidth
                            size="large"
                            onClick={handleSubmit}
                            disabled={isSubmitting || isRedirecting || !agreedToTerms || !paymentMethod}
                            sx={{ mt: 2 }}
                          >
                            {isRedirecting ? (
                              <Stack direction="row" spacing={1} alignItems="center">
                                <CircularProgress size={20} color="inherit" />
                                <Typography>{t("Redirecting to payment...")}</Typography>
                              </Stack>
                            ) : isSubmitting ? (
                              <Stack direction="row" spacing={1} alignItems="center">
                                <CircularProgress size={20} color="inherit" />
                                <Typography>{t("Processing...")}</Typography>
                              </Stack>
                            ) : (
                              t("Pay Now")
                            )}
                          </Button>
                        </Box>
                      )}
                    </Box>
                  ) : (
                    <Alert severity="info" sx={{ mb: 3 }}>
                      {t("No order information available")}
                    </Alert>
                  )}
                </>
              )}
            </Card>
          </Box>
        </CustomContainer>
      </MainLayout>
    </>
  );
};

export default AgencyPaymentPage;
export { getServerSideProps };