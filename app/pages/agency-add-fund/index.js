import React, { useState, useEffect } from "react";
import { useRouter } from "next/router";
import { useTranslation } from "react-i18next";
import { useSelector } from "react-redux";
import { toast } from "react-hot-toast";
import styled from "@emotion/styled";

// MUI components
import CssBaseline from "@mui/material/CssBaseline";
import {
  Box, Card, Typography, Button, Grid,
  Stack, Divider, FormControlLabel, Checkbox, Link,
  Alert, Chip, CircularProgress, Paper, TextField, IconButton,
  Modal, Fade, Backdrop
} from "@mui/material";
import { CheckCircle, Add, Delete, Close } from "@mui/icons-material";

// Custom components
import MainLayout from "../../src/components/layout/MainLayout";
import AuthGuard from "../../src/components/route-guard/AuthGuard";
import SEO from "../../src/components/seo";
import CustomContainer from "../../src/components/container";
import { CustomStackFullWidth } from "../../src/styled-components/CustomStyles.style";
import CustomImageContainer from "../../src/components/CustomImageContainer";
import { getAmountWithSign } from "../../src/helper-functions/CardHelpers";

// API hooks
import useGetWalletAgencyPaymentInfo from "../../src/api-manage/hooks/react-query/agency-add-fund/useGetWalletAgencyPaymentInfo";
import useGetAgencyPaymentAddFund from "../../src/api-manage/hooks/react-query/agency-add-fund/useGetAgencyPaymentAddFund";
import useGetAgencyPaymentSuccess from "../../src/api-manage/hooks/react-query/agency-add-fund/useGetAgencyPaymentSuccess";
import MainApi from "../../src/api-manage/MainApi";
import { copyToClipboard } from "../../src/utils/copyToClipboard";

// Server side props
import { getServerSideProps } from "../index";

// Error handling function
const handleApiError = (error) => {
  if (!error) return;
  
  const errorData = error.response?.data;
  if (!errorData) return;
  
  console.error("API Error:", errorData.message || JSON.stringify(errorData));
};

// 检查内容是否为空
const isEmptyContent = (content) => {
  if (!content) return true;
  if (content === '<p></p>') return true;
  return false;
};

// Custom payment option style component
const CustomRadioBox = styled(Box)(({ theme }) => ({
  label: {
    display: "flex",
    alignItems: "center",
    gap: "21px",
    cursor: "pointer",
    ".MuiSvgIcon-root": {
      width: "18px",
      height: "18px",
      color: theme?.palette?.primary?.main,
    },
    ">.MuiStack-root": {
      width: "0",
      flexGrow: "1",
    },
    padding: "8px 30px",
    borderRadius: "10px",
    "&.active": {
      background: theme?.palette?.background?.custom3 || "rgba(0, 0, 0, 0.04)",
    },
  },
}));

// 样式化的图片预览容器
const ImagePreviewContainer = styled(Box)(({ theme }) => ({
  position: 'relative',
  width: '100%',
  height: '100%',
  maxHeight: '90vh',
  maxWidth: '90vw',
  margin: 'auto',
  '& img': {
    width: '100%',
    height: '100%',
    objectFit: 'contain',
    maxHeight: '85vh',
  }
}));

const AgencyAddFundPage = ({ configData, landingPageData }) => {
  const router = useRouter();
  const { t } = useTranslation();
  const { agency_id, payment_id, flag } = router.query;
  
  // 获取当前用户信息
  const { profileInfo } = useSelector((state) => state.profileInfo);
  const userId = profileInfo?.id || null;

  // 基本状态管理
  const [missingAgencyId, setMissingAgencyId] = useState(false);
  const [agencyId, setAgencyId] = useState("");
  const [paymentMethod, setPaymentMethod] = useState(null);
  const [agreedToTerms, setAgreedToTerms] = useState(false);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [isRedirecting, setIsRedirecting] = useState(false);
  const [dataNotFound, setDataNotFound] = useState(false);
  const [isInitialized, setIsInitialized] = useState(false);
  const [paymentMethodValue, setPaymentMethodValue] = useState("");
  
  // 模板状态
  const [shareText, setShareText] = useState("");
  const [thankYouTemplate, setThankYouTemplate] = useState("");
  const [successData, setSuccessData] = useState(null);
  
  // 创建者编辑状态
  const [isCreator, setIsCreator] = useState(false);
  const [title, setTitle] = useState("");
  const [content, setContent] = useState("");
  const [images, setImages] = useState([]);
  const [imageFiles, setImageFiles] = useState([]);
  const [isSaving, setIsSaving] = useState(false);
  
  // Agency Content状态
  const [agencyContent, setAgencyContent] = useState(null);
  
  // 图片预览模态窗口状态
  const [previewOpen, setPreviewOpen] = useState(false);
  const [previewImage, setPreviewImage] = useState('');

  // 验证并设置Agency ID
  useEffect(() => {
    if (!agency_id) {
      setMissingAgencyId(true);
      setAgencyId("");
    } else {
      setMissingAgencyId(false);
      setAgencyId(agency_id);
    }
  }, [agency_id]);

  // 获取钱包支付信息 - 现在传递用户ID
  const {
    data: walletPaymentData,
    isLoading: walletPaymentLoading,
    error: walletPaymentError,
    refetch: refetchWalletPayment
  } = useGetWalletAgencyPaymentInfo(agencyId, { user_id: userId });

  // 获取分享模板信息
  const {
    data: shareData,
    isLoading: shareLoading
  } = useGetAgencyPaymentAddFund(agencyId || null);

  // 处理支付成功信息
  const {
    data: paymentSuccessData,
    isLoading: paymentSuccessLoading
  } = useGetAgencyPaymentSuccess(agencyId || null);

  // 检查所有API请求是否完成
  const allRequestsCompleted = !walletPaymentLoading && !shareLoading && !paymentSuccessLoading;

  // 处理API初始化完成状态
  useEffect(() => {
    if (allRequestsCompleted) {
      setIsInitialized(true);
      
      // 设置默认支付方式
      if (configData?.active_payment_method_list?.length > 0) {
        setPaymentMethodValue(configData.active_payment_method_list[0]?.gateway);
        setPaymentMethod(configData.active_payment_method_list[0]?.gateway);
      }
    }
  }, [allRequestsCompleted, configData]);

  // 处理API错误
  useEffect(() => {
    if (walletPaymentError) {
      handleApiError(walletPaymentError);
      
      const errorMessage = walletPaymentError.response?.data?.message;
      if (errorMessage === "Data not found") {
        setDataNotFound(true);
      }
    }
  }, [walletPaymentError]);

  // 处理数据不存在情况
  useEffect(() => {
    if (walletPaymentData && walletPaymentData.success === false && walletPaymentData.message === "Data not found") {
      setDataNotFound(true);
    }
  }, [walletPaymentData]);

  // 处理钱包支付数据，设置是否是创建者和agency_content
  useEffect(() => {
    if (!agencyId || !walletPaymentData?.wallet_payment) return;

    const { wallet_payment } = walletPaymentData;
    
    // 检查用户是否是创建者
    if (wallet_payment.is_creator) {
      setIsCreator(true);
      
      // 设置agency_content信息
      if (wallet_payment.agency_content) {
        setAgencyContent(wallet_payment.agency_content);
        
        // 如果是创建者，设置编辑表单的初始值
        if (wallet_payment.agency_content.title) {
          setTitle(wallet_payment.agency_content.title);
        }
        if (wallet_payment.agency_content.content) {
          setContent(wallet_payment.agency_content.content);
        }
        if (wallet_payment.agency_content.images && Array.isArray(wallet_payment.agency_content.images)) {
          setImages(wallet_payment.agency_content.images);
        }
      }
    } else {
      setIsCreator(false);
      
      // 非创建者也设置agency_content，但只用于展示
      if (wallet_payment.agency_content) {
        setAgencyContent(wallet_payment.agency_content);
      }
    }
  }, [walletPaymentData, agencyId]);

  // 处理分享模板和感谢模板数据
  useEffect(() => {
    if (!agencyId) return;

    // 设置分享文本
    if (shareData) {
      setShareText(shareData || '');
    }

    // 设置感谢模板和成功数据
    if (paymentSuccessData) {
      setThankYouTemplate(paymentSuccessData || '');
      setSuccessData(paymentSuccessData || '');
    }
  }, [shareData, paymentSuccessData, agencyId]);

  // 表单提交处理
  const handleSubmit = async (e) => {
    e.preventDefault();
  
    // 验证表单
    if (!agreedToTerms) {
      toast.error(t("Please agree to the terms and conditions"));
      return;
    }
  
    if (!paymentMethod) {
      toast.error(t("Please select a payment method"));
      return;
    }
  
    setIsSubmitting(true);
    try {
      // 修改这里：确保 callback 只包含 agency_id 参数
      const currentUrl = window.location.origin + `/agency-add-fund?agency_id=${agencyId}`;
      
      // 构建支付数据
      const payloadData = {
        amount: walletPaymentData?.wallet_payment?.amount,
        payment_method: paymentMethod,
        agency_id: agencyId,
        callback: currentUrl,
        payment_platform: "web"
      };
  
      // 调用钱包充值API
      const { data } = await MainApi.post('/api/v1/customer/wallet/process-agency-payment', payloadData);
      
      setIsSubmitting(false);
      if (data?.redirect_link) {
        setIsRedirecting(true);
        router.push(data.redirect_link);
      } else {
        toast.success(t("Payment request submitted successfully"));
        // 修改这里：成功后跳转也只带 agency_id 参数
        router.push(`/agency-add-fund?agency_id=${agencyId}&flag=success`);
      }
    } catch (error) {
      console.error("Payment processing error:", error);
      handleApiError(error);
      toast.error(error?.response?.data?.message || t("An error occurred while processing payment"));
      setIsSubmitting(false);
    }
  };

  // 处理图片上传
  const handleImageUpload = (e) => {
    const files = Array.from(e.target.files);
    if (files.length === 0) return;
    
    // 预览处理
    const newImageFiles = [...imageFiles];
    
    files.forEach(file => {
      // 验证文件类型
      if (!file.type.startsWith('image/')) {
        toast.error(t("Please upload only image files"));
        return;
      }
      
      // 创建预览URL
      const imageUrl = URL.createObjectURL(file);
      newImageFiles.push({
        file,
        preview: imageUrl
      });
    });
    
    setImageFiles(newImageFiles);
  };
  
  // 移除图片
  const handleRemoveImage = (index) => {
    const newImageFiles = [...imageFiles];
    
    // 如果存在预览URL，释放它
    if (newImageFiles[index]?.preview) {
      URL.revokeObjectURL(newImageFiles[index].preview);
    }
    
    newImageFiles.splice(index, 1);
    setImageFiles(newImageFiles);
  };
  
  // 移除已有图片
  const handleRemoveExistingImage = (index) => {
    const newImages = [...images];
    newImages.splice(index, 1);
    setImages(newImages);
  };

  // 保存创建者编辑的内容
  const handleSaveCreatorContent = async () => {
    if (!title.trim()) {
      toast.error(t("Please enter a title"));
      return;
    }
    
    setIsSaving(true);
    
    try {
      // 准备表单数据
      const formData = new FormData();
      formData.append('agency_id', agencyId);
      formData.append('title', title);
      formData.append('content', content);
      
      // 添加现有图片ID
      images.forEach((image, index) => {
        formData.append(`existing_images[${index}]`, image.id || image);
      });
      
      // 添加新图片
      imageFiles.forEach((item, index) => {
        formData.append(`new_images[${index}]`, item.file);
      });
      
      // 发送更新请求
      await MainApi.post('/api/v1/update-agency-payment-content', formData);
      
      toast.success(t("Content updated successfully"));
      
      // 刷新数据
      await refetchWalletPayment();
      
      // 清理图片预览URL
      imageFiles.forEach(item => {
        if (item.preview) URL.revokeObjectURL(item.preview);
      });
      
      setImageFiles([]);
    } catch (error) {
      console.error("Error saving content:", error);
      handleApiError(error);
      toast.error(error?.response?.data?.message || t("Failed to update content"));
    } finally {
      setIsSaving(false);
    }
  };

  // 图片预览相关处理函数
  const handlePreview = (imageSrc) => {
    setPreviewImage(imageSrc);
    setPreviewOpen(true);
  };

  const handlePreviewClose = () => {
    setPreviewOpen(false);
  };

  // 渲染支付状态芯片
  const renderPaymentStatusChip = (status) => {
    let color = "default";
    if (status === "success") color = "success";
    else if (status === "pending") color = "warning";
    else if (status === "failed") color = "error";

    return (
      <Chip
        label={t(status.charAt(0).toUpperCase() + status.slice(1))}
        color={color}
        size="small"
        variant="outlined"
      />
    );
  };

  // 获取钱包支付信息
  const walletPayment = walletPaymentData?.wallet_payment || null;

  // 显示逻辑标志
  const isPaymentSuccess = flag === "success" || (walletPayment && walletPayment.payment_status === "success");
  const isPaymentPending = walletPayment && walletPayment.payment_status === "pending";
  const isPaymentCancelled = flag === "cancel";
  const showLoading = walletPaymentLoading || !isInitialized;
  
  // 修改显示逻辑，仅在非创建者情况下显示支付表单
  const showPaymentForm = !isCreator && !isPaymentSuccess && (isPaymentPending || isPaymentCancelled || !walletPayment?.payment_status);
  
  // 修改显示逻辑，仅在非创建者情况下显示分享信息
  const showShareInfo = !isCreator && !isPaymentSuccess && !dataNotFound && !isEmptyContent(shareText);
  
  // 创建者欢迎信息
  const showCreatorWelcome = isCreator && !dataNotFound;

  return (
    <>
      <CssBaseline />
      <SEO
        title={configData ? t("Agency Add Fund") : t("Loading...")}
        image={`${configData?.base_urls?.business_logo_url}/${configData?.fav_icon}`}
        businessName={configData?.business_name}
      />

      <MainLayout configData={configData} landingPageData={landingPageData}>
        <CustomContainer>
          <Box sx={{ py: 4 }}>
            <Card sx={{ p: 3, maxWidth: 800, mx: "auto" }}>
              {/* 主显示逻辑 */}
              {showLoading ? (
                // 加载状态
                <Box sx={{ p: 4, textAlign: 'center' }}>
                  <CircularProgress size={40} sx={{ mb: 2 }} />
                  <Typography>{t("Loading payment information...")}</Typography>
                </Box>
              ) : missingAgencyId ? (
                <Alert severity="error" sx={{ 
                  mb: 3,
                  '& .MuiAlert-message': {
                    width: '100%',
                  },
                  alignItems: 'center'
                }}>
                  {t("Missing required parameter: Agency ID. Please provide a valid agency ID.")}
                </Alert>
              ) : dataNotFound ? (
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
                  {t("Data not found. The agency may not exist or the ID is invalid.")}
                </Alert>
              ) : (
                <>
                  {/* 创建者欢迎信息 - 当用户是创建者时显示 */}
                  {showCreatorWelcome && (
                    <Box sx={{ mb: 4, p: 2, bgcolor: 'background.paper', borderRadius: 1, border: '1px solid', borderColor: 'primary.light' }}>
                      <Typography variant="h6" color="primary.main" gutterBottom>
                        {t("Creator Dashboard")}
                      </Typography>
                      <Typography variant="body1" sx={{ mb: 2 }}>
                        {t("Welcome to your fundraising page. As the creator, you can edit the content of this page, but you don't need to make any payments.")}
                      </Typography>
                      
                      {/* 显示募集金额信息 */}
                      {walletPayment && (
                        <Paper sx={{ p: 2, mb: 2, bgcolor: 'primary.lighter' }} variant="outlined">
                          <Typography variant="subtitle2">
                            {t("Fundraising Target")}
                          </Typography>
                          <Typography variant="h5" color="primary.main" sx={{ mt: 1 }}>
                            {getAmountWithSign(walletPayment.amount)}
                          </Typography>
                        </Paper>
                      )}
                      
                      {/* 添加分享链接功能 */}
                      <Box sx={{ mt: 2 }}>
                        <Typography variant="subtitle2" gutterBottom>
                          {t("Share your fundraising page")}
                        </Typography>
                        <TextField
                          fullWidth
                          variant="outlined"
                          size="small"
                          value={typeof window !== 'undefined' ? window.location.href : ''}
                          InputProps={{
                            readOnly: true,
                            endAdornment: (
                              <Button
                                variant="contained"
                                size="small"
                                onClick={() => {
                                  copyToClipboard(window.location.href).then(success => {
                                    if (success) {
                                      toast.success(t("Link copied to clipboard"));
                                    } else {
                                      toast.error(t("Failed to copy link"));
                                    }
                                  });
                                }}
                              >
                                {t("Copy")}
                              </Button>
                            )
                          }}
                        />
                      </Box>
                    </Box>
                  )}
                
                  {/* 创建者编辑区域 - 仅当用户是创建者时显示 */}
                  {isCreator && (
                    <Box sx={{ mb: 4, p: 2, bgcolor: 'background.paper', borderRadius: 1, border: '1px solid', borderColor: 'primary.light' }}>
                      <Typography variant="h6" color="primary.main" gutterBottom>
                        {t("Edit Content")}
                      </Typography>
                      
                      <Grid container spacing={2} sx={{ mt: 1 }}>
                        <Grid item xs={12}>
                          <TextField
                            label={t("Title")}
                            variant="outlined"
                            fullWidth
                            value={title}
                            onChange={(e) => setTitle(e.target.value)}
                            required
                          />
                        </Grid>
                        
                        <Grid item xs={12}>
                          <TextField
                            label={t("Content")}
                            variant="outlined"
                            fullWidth
                            multiline
                            rows={4}
                            value={content}
                            onChange={(e) => setContent(e.target.value)}
                          />
                        </Grid>
                        
                        <Grid item xs={12}>
                          <Typography variant="subtitle2" gutterBottom>
                            {t("Images")}
                          </Typography>
                          
                          {/* 已有图片展示 */}
                          {images.length > 0 && (
                            <Box sx={{ display: 'flex', flexWrap: 'wrap', gap: 1, mb: 2 }}>
                              {images.map((image, index) => (
                                <Box 
                                  key={`existing-${index}`}
                                  sx={{ 
                                    position: 'relative',
                                    width: 100,
                                    height: 100,
                                    borderRadius: 1,
                                    overflow: 'hidden',
                                    cursor: 'pointer'
                                  }}
                                >
                                  <Box
                                    onClick={() => handlePreview(image.url || image)}
                                    sx={{
                                      width: '100%',
                                      height: '100%',
                                    }}
                                  >
                                    <CustomImageContainer
                                      src={image.url || image} // 兼容对象或字符串格式
                                      width="100%"
                                      height="100%"
                                      objectfit="cover"
                                    />
                                  </Box>
                                  <IconButton
                                    size="small"
                                    sx={{
                                      position: 'absolute',
                                      top: 4,
                                      right: 4,
                                      bgcolor: 'rgba(255,255,255,0.8)',
                                      '&:hover': {
                                        bgcolor: 'rgba(255,255,255,0.9)',
                                      }
                                    }}
                                    onClick={() => handleRemoveExistingImage(index)}
                                  >
                                    <Delete fontSize="small" />
                                  </IconButton>
                                </Box>
                              ))}
                            </Box>
                          )}
                          
                          {/* 新上传图片预览 */}
                          {imageFiles.length > 0 && (
                            <Box sx={{ display: 'flex', flexWrap: 'wrap', gap: 1, mb: 2 }}>
                              {imageFiles.map((item, index) => (
                                <Box 
                                  key={`new-${index}`}
                                  sx={{ 
                                    position: 'relative',
                                    width: 100,
                                    height: 100,
                                    borderRadius: 1,
                                    overflow: 'hidden',
                                    cursor: 'pointer'
                                  }}
                                >
                                  <Box
                                    onClick={() => handlePreview(item.preview)}
                                    sx={{
                                      width: '100%',
                                      height: '100%',
                                    }}
                                  >
                                    <img
                                      src={item.preview}
                                      alt={`Upload preview ${index}`}
                                      style={{
                                        width: '100%',
                                        height: '100%',
                                        objectFit: 'cover'
                                      }}
                                    />
                                  </Box>
                                  <IconButton
                                    size="small"
                                    sx={{
                                      position: 'absolute',
                                      top: 4,
                                      right: 4,
                                      bgcolor: 'rgba(255,255,255,0.8)',
                                      '&:hover': {
                                        bgcolor: 'rgba(255,255,255,0.9)',
                                      }
                                    }}
                                    onClick={() => handleRemoveImage(index)}
                                  >
                                    <Delete fontSize="small" />
                                  </IconButton>
                                </Box>
                              ))}
                            </Box>
                          )}
                          
                          <Box sx={{ display: 'flex', gap: 2, alignItems: 'center', mt: 1 }}>
                            <Button
                              variant="outlined"
                              component="label"
                              startIcon={<Add />}
                              size="small"
                            >
                              {t("Add Images")}
                              <input
                                type="file"
                                multiple
                                accept="image/*"
                                onChange={handleImageUpload}
                                hidden
                              />
                            </Button>
                            <Typography variant="caption" color="text.secondary">
                              {t("Supported formats: JPG, PNG, GIF. Max 2MB per image.")}
                            </Typography>
                          </Box>
                        </Grid>
                        
                        <Grid item xs={12}>
                          <Button
                            variant="contained"
                            color="primary"
                            disabled={isSaving || !title}
                            onClick={handleSaveCreatorContent}
                            sx={{ mt: 1 }}
                          >
                            {isSaving ? (
                              <Box sx={{ display: 'flex', alignItems: 'center', gap: 1 }}>
                                <CircularProgress size={20} color="inherit" />
                                <Typography>{t("Saving...")}</Typography>
                              </Box>
                            ) : (
                              t("Save Changes")
                            )}
                          </Button>
                        </Grid>
                      </Grid>
                    </Box>
                  )}

                  {/* 支付取消提示 - 仅非创建者显示 */}
                  {!isCreator && isPaymentCancelled && (
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
                      {t("Payment has been cancelled. If you would like to complete the payment, please try again.")}
                    </Alert>
                  )}

                  {/* 支付成功区域 - 仅非创建者显示 */}
                  {!isCreator && isPaymentSuccess && !isEmptyContent(thankYouTemplate) && (
                    <Box sx={{ mb: 4 }}>
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
                        
                        {paymentSuccessLoading ? (
                          <Box sx={{ textAlign: 'center', py: 2 }}>
                            <CircularProgress size={24} sx={{ mb: 1 }} />
                            <Typography variant="body2">{t("Loading payment success details...")}</Typography>
                          </Box>
                        ) : (
                          <Box
                            sx={{
                              '& a': { color: 'primary.main' },
                              '& img': { maxWidth: '100%', height: 'auto', borderRadius: '4px' }
                            }}
                          >
                            <Typography variant="body2" dangerouslySetInnerHTML={{ __html: thankYouTemplate }} />
                          </Box>
                        )}
                      </Box>
                    </Box>
                  )}
                  
                  {/* 支付成功但没有模板内容时简单显示 - 仅非创建者显示 */}
                  {!isCreator && isPaymentSuccess && isEmptyContent(thankYouTemplate) && (
                    <Box sx={{ mb: 4 }}>
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
                        <Typography variant="body2">
                          {t("Thank you for your payment. Your funds have been added successfully.")}
                        </Typography>
                      </Box>
                    </Box>
                  )}

                  {/* 支付失败或其他状态提示 - 仅非创建者显示 */}
                  {!isCreator && walletPayment && walletPayment.payment_status === "failed" && (
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
                      {t("Payment failed. Please try again or contact support.")}
                    </Alert>
                  )}

                  {/* 分享信息区域 - 仅当shareText不为空时才显示 */}
                  {showShareInfo && (
                    <Stack spacing={2} sx={{ mb: 3 }}>
                      {shareLoading ? (
                        <Box sx={{ p: 2, bgcolor: 'background.paper', borderRadius: 1, opacity: 0.7 }}>
                          <Typography variant="body2">{t("Loading share information...")}</Typography>
                        </Box>
                      ) : (
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
                            <Typography variant="body2" dangerouslySetInnerHTML={{ __html: shareText }} />
                          </Box>
                        </Box>
                      )}
                    </Stack>
                  )}

                  {!isCreator && <Divider sx={{ mb: 3 }} />}

                  {/* Agency Content 显示区域 - 移动到分割线下面，钱包支付信息上面 */}
                  {agencyContent && !isCreator && (
                    <Box sx={{ mb: 4 }}>
                      <Paper 
                        elevation={0} 
                        sx={{ 
                          p: 3, 
                          mb: 3, 
                          border: '1px solid', 
                          borderColor: 'divider',
                          borderRadius: 1 
                        }}
                      >
                        {agencyContent.title && (
                          <Typography variant="h5" gutterBottom>
                            {agencyContent.title}
                          </Typography>
                        )}
                        
                        {agencyContent.content && !isEmptyContent(agencyContent.content) && (
                          <Typography variant="body1" sx={{ mb: 2 }}>
                            {agencyContent.content}
                          </Typography>
                        )}
                        
                        {agencyContent.images && agencyContent.images.length > 0 && (
                          <Box sx={{ display: 'flex', flexWrap: 'wrap', gap: 2, mt: 2 }}>
                            {agencyContent.images.map((image, index) => (
                              <Box
                                key={`content-image-${index}`}
                                sx={{
                                  width: { xs: '100%', sm: '48%', md: '31%' },
                                  borderRadius: 1,
                                  overflow: 'hidden',
                                  cursor: 'pointer'
                                }}
                                onClick={() => handlePreview(image.url || image)}
                              >
                                <CustomImageContainer
                                  src={image.url || image}
                                  width="100%"
                                  height="200px"
                                  objectfit="cover"
                                  borderRadius="8px" // 添加圆角属性
                                  style={{ borderRadius: '8px' }} // 额外确保圆角样式
                                />
                              </Box>
                            ))}
                          </Box>
                        )}
                      </Paper>
                    </Box>
                  )}

                  {/* 钱包支付信息 - 仅非创建者显示 */}
                  {!isCreator && walletPayment && (
                    <Box sx={{ mb: 4 }}>
                      {/* 金额信息 */}
                      <Paper sx={{ p: 2, mb: 3 }} variant="outlined">
                        <Typography variant="subtitle1" gutterBottom>
                          {t("Payment Amount")}
                        </Typography>
                        <Typography variant="h4" color="primary.main" sx={{ mt: 1 }}>
                          {getAmountWithSign(walletPayment.amount)}
                        </Typography>
                      </Paper>
                    </Box>
                  )}

                  {/* 支付选项表单 - 仅非创建者显示 */}
                  {showPaymentForm && (
                    <Box component="form" onSubmit={handleSubmit} sx={{ mb: 4 }}>
                      <Typography variant="h6" gutterBottom>
                        {t("Select Payment Method")}
                      </Typography>
                      
                      <Box sx={{ mt: 3, mb: 3 }}>
                        <Stack spacing={1}>
                          {configData?.active_payment_method_list?.map((item) => (
                            <CustomRadioBox key={item?.gateway}>
                              <label className={paymentMethodValue === item.gateway ? "active" : ""}>
                                <input
                                  type="radio"
                                  name="payment_method"
                                  value={item?.gateway}
                                  onChange={(e) => {
                                    setPaymentMethodValue(e.target.value);
                                    setPaymentMethod(e.target.value);
                                  }}
                                  style={{ display: "none" }}
                                />
                                {paymentMethodValue === item.gateway ? (
                                  <CheckCircle />
                                ) : (
                                  <Box
                                    sx={{
                                      width: "18px",
                                      borderRadius: "50%",
                                      aspectRatio: "1",
                                      border: `1px solid #e0e0e0`,
                                    }}
                                  />
                                )}
                                <Stack
                                  direction="row"
                                  gap={1}
                                  sx={{
                                    img: {
                                      height: "24px",
                                      width: "unset",
                                    },
                                  }}
                                >
                                  {item?.gateway_image && (
                                    <CustomImageContainer
                                      src={`${configData?.base_urls?.gateway_image_url}/${item?.gateway_image}`}
                                      width="30px"
                                      height="30px"
                                      objectfit="contain"
                                    />
                                  )}
                                  <Typography fontSize="14px">
                                    {item?.gateway_title}
                                  </Typography>
                                </Stack>
                              </label>
                            </CustomRadioBox>
                          ))}
                        </Stack>
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
                        disabled={isSubmitting || isRedirecting || !agreedToTerms || !paymentMethod}
                        sx={{ mt: 2 }}
                      >
                        {isRedirecting ? (
                          <Stack direction="row" spacing={1} alignItems="center">
                            <CircularProgress size={20} color="inherit" />
                            <Typography>{t("Redirecting to payment page...")}</Typography>
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
                </>
              )}
            </Card>
          </Box>
        </CustomContainer>
      </MainLayout>

      {/* 图片预览模态窗口 */}
      <Modal
        open={previewOpen}
        onClose={handlePreviewClose}
        closeAfterTransition
        BackdropComponent={Backdrop}
        BackdropProps={{
          timeout: 500,
        }}
        sx={{
          display: 'flex',
          alignItems: 'center',
          justifyContent: 'center',
          p: 2
        }}
      >
        <Fade in={previewOpen}>
          <Box
            sx={{
              position: 'relative',
              bgcolor: 'background.paper',
              boxShadow: 24,
              p: 1,
              borderRadius: 1,
              maxWidth: '90vw',
              maxHeight: '90vh',
              overflowY: 'auto'
            }}
          >
            <IconButton
              onClick={handlePreviewClose}
              sx={{
                position: 'absolute',
                right: 8,
                top: 8,
                color: 'text.secondary',
                bgcolor: 'rgba(255, 255, 255, 0.7)',
                zIndex: 1,
                '&:hover': {
                  bgcolor: 'rgba(255, 255, 255, 0.9)'
                }
              }}
            >
              <Close />
            </IconButton>
            <ImagePreviewContainer>
              <img src={previewImage} alt="Preview" />
            </ImagePreviewContainer>
          </Box>
        </Fade>
      </Modal>
    </>
  );
};

export default AgencyAddFundPage;
export { getServerSideProps };