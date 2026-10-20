{{-- 重置密码验证码邮件模板（内联样式，兼容 QQ 邮箱客户端） --}}
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>基层医生DR平台 - 重置密码验证码</title>
</head>
<body style="margin:0; padding:0; background-color:#f4f6f9;">

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f6f9; padding:32px 16px;">
        <tr>
            <td align="center">

                <table role="presentation" width="560" cellpadding="0" cellspacing="0" style="max-width:560px; width:100%; background-color:#ffffff; border-radius:12px; overflow:hidden; box-shadow:0 2px 12px rgba(0,0,0,0.08);">

                    {{-- 顶部品牌栏 --}}
                    <tr>
                        <td align="center" style="background:linear-gradient(135deg,#1a73e8,#0d47a1); padding:28px 24px;">
                            <p style="margin:0; color:#ffffff; font-size:22px; font-weight:bold; letter-spacing:2px;">基层医生 DR 平台</p>
                            <p style="margin:8px 0 0 0; color:#dce7fb; font-size:13px;">重置密码验证码</p>
                        </td>
                    </tr>

                    {{-- 正文 --}}
                    <tr>
                        <td style="padding:32px 36px;">
                            <p style="margin:0 0 12px 0; color:#333333; font-size:15px; line-height:1.7;">您好，</p>
                            <p style="margin:0 0 24px 0; color:#333333; font-size:15px; line-height:1.7;">
                                您正在申请重置登录密码，本次验证码为：
                            </p>

                            {{-- 验证码 --}}
                            <p style="margin:0 0 24px 0; text-align:center; color:#1a73e8; font-size:38px; font-weight:bold; letter-spacing:8px; background-color:#eef4ff; border-radius:8px; padding:18px 0;">
                                {{ $code }}
                            </p>

                            <p style="margin:0 0 8px 0; color:#666666; font-size:14px; line-height:1.7;">
                                验证码 <span style="color:#e53935; font-weight:bold;">{{ $expireMinutes }}</span> 分钟内有效，请勿泄露给他人。
                            </p>
                            <p style="margin:0 0 24px 0; color:#666666; font-size:14px; line-height:1.7;">
                                若非本人操作，请忽略本邮件，您的账户不会受到影响。
                            </p>

                            {{-- 分割线 --}}
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-top:1px solid #ececec; margin:0 0 16px 0;">
                                <tr><td style="height:1px; font-size:0; line-height:0;">&nbsp;</td></tr>
                            </table>

                            <p style="margin:0; color:#999999; font-size:12px; line-height:1.7; text-align:center;">
                                此邮件由系统自动发送，请勿直接回复。<br>
                                © 基层医生 DR 医学科普视频学习考核平台
                            </p>
                        </td>
                    </tr>
                </table>

            </td>
        </tr>
    </table>

</body>
</html>
