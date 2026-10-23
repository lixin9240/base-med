# 对接平台 数据库表设计

---

**文档控制**

| 项目 | 内容 |
| ---- | ---- |
| 版本 | V1.0 |
| 建立日期 | - |
| 编制人 | - |
| 审核人 | - |
| 批准人 | - |
| 批准日期 | - |

---

# 1 设计说明

- 本库用于对接平台客户端落地存储从开放平台拉取的基础档案、就诊、检验/检查等数据（对应《对接平台接口文档》#2 各接口返回的业务对象）。
- 表名统一以 `conn_`（connection/docking）为前缀，共 19 张表，按业务域划分为三类：
  - **1 机构/组织基础档案**
  - **2 居民（患者）健康档案**
  - **3 就诊与临床数据**
- 主键统一使用雪花/分布式 `bigint`；业务唯一键用唯一索引约束。
- 时间字段统一采用 `datetime`。
- 标准编码字段（GB/T、FH 值域）额外存储「编码值 + 名称」双字段。
- 涉隐私字段（身份证号等）建议加密或脱敏存储。

---

# 2 机构/组织基础档案

## 2.1 conn_org 机构表

| 字段 | 类型 | 是否必须 | 说明 |
| ---- | ---- | ---- | ---- |
| id | bigint | 主键 | 主键 ID（雪花） |
| org_id | bigint | 是 | 机构唯一标识 |
| org_name | varchar(255) | 是 | 机构名称 |
| create_time | datetime | 是 | 创建时间 |
| update_time | datetime | 否 | 更新时间 |

## 2.2 conn_campus 院区表

| 字段 | 类型 | 是否必须 | 说明 |
| ---- | ---- | ---- | ---- |
| id | bigint | 主键 | 主键 ID |
| campus_id | bigint | 是 | 院区唯一标识 |
| campus_name | varchar(255) | 是 | 院区名称 |
| org_id | bigint | 是 | 所属机构 ID |
| create_time / update_time | datetime | 是 / 否 | 创建 / 更新时间 |

## 2.3 conn_area 病区表

| 字段 | 类型 | 是否必须 | 说明 |
| ---- | ---- | ---- | ---- |
| id | bigint | 主键 | 主键 ID |
| area_id | bigint | 是 | 病区唯一标识 |
| area_name | varchar(255) | 是 | 病区名称 |
| org_id | bigint | 是 | 所属机构 ID |
| campus_id | bigint | 否 | 所属院区 ID |
| create_time / update_time | datetime | 是 / 否 | 创建 / 更新时间 |

## 2.4 conn_dept 科室表

（对应接口 2.2）

| 字段 | 类型 | 是否必须 | 说明 |
| ---- | ---- | ---- | ---- |
| id | bigint | 主键 | 主键 ID |
| org_id | bigint | 是 | 机构 ID |
| campus_id | bigint | 否 | 院区 ID |
| dept_id | varchar(64) | 是 | 科室唯一标识（唯一索引） |
| dept_name | varchar(255) | 是 | 科室名称 |
| parent_dept_id | varchar(64) | 否 | 上级科室 ID（树形） |
| parent_business_dept_id | bigint | 否 | 父级业务部门 ID |
| parent_business_name | varchar(255) | 否 | 父级业务部门名称 |
| dept_type_code | varchar(32) | 是 | 科室类型编码（FH0132.01-医疗，.02-教学，.03-科研，.04-行政，.05-后勤，.06-其他） |
| dept_type_name | varchar(64) | 是 | 科室类型名称 |
| subject_code / subject_name | varchar(64) | 否 | 国际学科编码 / 名称 |
| org_subject_code / org_subject_name | varchar(64) | 否 | 机构学科编码 / 名称 |
| pinyin | varchar(255) | 否 | 拼音 |
| pinyin_initials | varchar(64) | 否 | 拼音首字母 |
| wbm | varchar(64) | 否 | 五笔 |
| inpatient_area | tinyint | 否 | 是否病区：0-不是，1-是 |
| service_scopes | varchar(512) | 否 | 服务范围，多值逗号分隔（emergency/outpatient/hospitalization/operation 等） |
| status | tinyint | 是 | 0-停用，1-启用 |
| create_time / update_time | datetime | 是 / 否 | 创建 / 更新时间 |

## 2.5 conn_staff 职工表

（对应接口 2.1）

| 字段 | 类型 | 是否必须 | 说明 |
| ---- | ---- | ---- | ---- |
| id | bigint | 主键 | 主键 ID |
| org_id | bigint | 是 | 机构 ID |
| staff_id | bigint | 是 | 职工唯一标识（唯一索引） |
| job_number | varchar(64) | 是 | 职工工号（唯一索引） |
| account_no | varchar(64) | 否 | 职工系统账号 |
| name | varchar(128) | 是 | 职工姓名 |
| pinyin | varchar(255) | 否 | 姓名全拼 |
| pinyin_initials | varchar(64) | 否 | 姓名拼音首字母 |
| wbm | varchar(64) | 否 | 五笔编码 |
| gender_code / gender_name | varchar(32) / varchar(16) | 是 | 性别编码（GB/T2261.1）/ 性别名称 |
| telephone | varchar(32) | 否 | 联系电话 |
| birthday | datetime | 否 | 生日 |
| marital_status_code | varchar(32) | 否 | 婚姻状况编码（GB/T2261.2：10-未婚，20-已婚，21-初婚，22-再婚，23-复婚） |
| personnel_dept_id / personnel_dept_name | varchar(64) / varchar(255) | 是 | 人事科室 ID / 名称 |
| business_dept_id / business_dept_name | varchar(64) / varchar(255) | 否 | 业务科室 ID / 名称 |
| status | tinyint | 否 | 0-停用，1-未停用 |
| create_time / update_time | datetime | 是 / 否 | 创建 / 更新时间 |

## 2.6 conn_staff_dept 职工-科室关联表

（多对多，支撑 businessDepts/personnelDepts）

| 字段 | 类型 | 是否必须 | 说明 |
| ---- | ---- | ---- | ---- |
| id | bigint | 主键 | 主键 ID |
| staff_id | bigint | 是 | 职工 ID |
| dept_id | bigint | 是 | 科室 ID |
| dept_code | varchar(64) | 否 | 科室编码 |
| dept_name | varchar(255) | 否 | 科室名称 |
| dept_dimension | tinyint | 是 | 科室维度：201-人事，202-业务 |
| create_time | datetime | 是 | 创建时间 |

## 2.7 conn_staff_qualification 职工资质表

（对应接口 2.1 qualifications，GB/T8561）

| 字段 | 类型 | 是否必须 | 说明 |
| ---- | ---- | ---- | ---- |
| id | bigint | 主键 | 主键 ID |
| staff_id | bigint | 是 | 职工 ID |
| practice_type_code / practice_type_name | varchar(32) / varchar(128) | 是 | 执业类别编码（GB/T8561.230）/ 名称 |
| professional_title_code / professional_title_name | varchar(32) / varchar(128) | 否 | 专业技术职务编码（GB/T8561.231）/ 名称 |
| create_time | datetime | 是 | 创建时间 |

## 2.8 conn_bed 床位表

（对应接口 2.3）

| 字段 | 类型 | 是否必须 | 说明 |
| ---- | ---- | ---- | ---- |
| id | bigint | 主键 | 主键 ID |
| org_id | bigint | 是 | 机构 ID |
| campus_id | bigint | 否 | 院区 ID |
| code | varchar(64) | 是 | 床位唯一编码 |
| bed_number | varchar(64) | 是 | 床位号 |
| area_id / area_name | bigint / varchar(255) | 否 | 病区 ID / 名称 |
| dept_id / dept_name | bigint / varchar(255) | 否 | 科室 ID / 名称（可为空） |
| room_no | varchar(64) | 否 | 房间号 |
| bed_property | tinyint | 否 | 床位属性：1-普通床，2-新生儿虚拟床，3-通用虚拟床（血透虚拟床） |
| bed_type_id / bed_type_name | bigint / varchar(128) | 否 | 床位类型 ID / 名称 |
| bed_region_id / bed_region_name | bigint / varchar(128) | 否 | 床位区域 ID / 名称 |
| is_effective | tinyint | 否 | 是否有效：1-有效，2-失效 |
| create_time / update_time | datetime | 是 / 否 | 创建 / 更新时间 |

---

# 3 居民（患者）健康档案

## 3.1 conn_rhc_regist 居民档案登记表

（对应接口 2.4 data 顶层）

| 字段 | 类型 | 是否必须 | 说明 |
| ---- | ---- | ---- | ---- |
| id | bigint | 主键 | 主键 ID |
| org_id | bigint | 是 | 机构 ID |
| org | varchar(255) | 是 | 机构名称 |
| patient_id | varchar(64) | 是 | 患者 ID（唯一索引） |
| medical_record_no | varchar(64) | 是 | 病历号（机构内唯一索引） |
| master_index_id | bigint | 否 | 居民主索引 ID |
| register_time | datetime | 否 | 登记时间 |
| registrant_id | varchar(64) | 否 | 登记人 ID |
| registrant | varchar(128) | 否 | 登记人姓名 |
| id_card_no | varchar(32) | 否 | 居民身份证号（脱敏） |
| education_code | varchar(32) | 否 | 学历编码 |
| marriage_code | varchar(32) | 否 | 婚姻状况编码 |
| remark | varchar(512) | 否 | 备注 |
| status | tinyint | 否 | 0-停用，1-未停用 |
| feature_param | text | 否 | 扩展特征参数（JSON） |
| create_time / update_time | datetime | 是 / 否 | 创建 / 更新时间 |

## 3.2 conn_rhc_master_index 居民主索引表

（ResidentMasterIndexDTO）

| 字段 | 类型 | 是否必须 | 说明 |
| ---- | ---- | ---- | ---- |
| id | bigint | 主键 | 主键 ID（master_index_id） |
| name | varchar(128) | 是 | 姓名 |
| name_pinyin | varchar(128) | 否 | 姓名拼音 |
| gender_code / gender | varchar(32) / varchar(16) | 否 | 性别编码（GB/T2261.1）/ 名称 |
| birth_date | datetime | 否 | 出生日期 |
| age | varchar(32) | 否 | 年龄描述（如「2岁5月」） |
| nationality_code / nationality | varchar(32) / varchar(64) | 否 | 国籍编码 / 名称 |
| education / education_code | varchar(64) / varchar(32) | 否 | 学历 / 学历编码 |
| marriage / marriage_code | varchar(64) / varchar(32) | 否 | 婚姻 / 婚姻编码 |
| status | tinyint | 否 | 1-使用中，2-已停用，3-已注销 |
| create_time / update_time | datetime | 是 / 否 | 创建 / 更新时间 |

## 3.3 conn_rhc_card 居民卡/证件表

（ResidentCardInfoDTO，对应 residentCardInfos / residentCertificateCardInfos）

| 字段 | 类型 | 是否必须 | 说明 |
| ---- | ---- | ---- | ---- |
| id | bigint | 主键 | 主键 ID |
| card_id | bigint | 否 | 卡 ID |
| master_index_id | bigint | 否 | 居民主索引 ID |
| resident_name | varchar(128) | 否 | 居民姓名 |
| resident_card_no | varchar(64) | 否 | 卡号 |
| card_name | varchar(128) | 否 | 卡名称 |
| card_alias | varchar(128) | 否 | 卡别名 |
| card_type_code | varchar(64) | 否 | 证件类型编码（如 CT/JM0001_1.02） |
| card_type | varchar(128) | 否 | 证件类型描述 |
| restrictions / restrictions_desc | varchar(64) / varchar(128) | 否 | 限制条件代码（如 GB/T2659.156）/ 描述 |
| status | tinyint | 否 | 0-停用，1-启用，2-合并停用 |
| card_kind | tinyint | 否 | 卡类型：1-居民卡，2-证件档案 |
| feature_param | text | 否 | 扩展参数（JSON） |
| create_time / update_time | datetime | 是 / 否 | 创建 / 更新时间 |

## 3.4 conn_rhc_medical_card 医疗卡表

（ResidentMedicalCardInfoDTO）

| 字段 | 类型 | 是否必须 | 说明 |
| ---- | ---- | ---- | ---- |
| id | bigint | 主键 | 主键 ID |
| master_index_id | bigint | 否 | 居民主索引 ID |
| resident_card_no | varchar(64) | 否 | 医疗卡号 |
| card_name / card_alias | varchar(128) | 否 | 卡名称 / 别名 |
| create_time / update_time | datetime | 是 / 否 | 创建 / 更新时间 |

## 3.5 conn_rhc_contact 联系人表

（ResidentContactInfoDTO）

| 字段 | 类型 | 是否必须 | 说明 |
| ---- | ---- | ---- | ---- |
| id | bigint | 主键 | 主键 ID |
| master_index_id | bigint | 否 | 居民主索引 ID |
| resident_name | varchar(128) | 否 | 居民姓名 |
| relationship_code / relationship | varchar(32) / varchar(64) | 否 | 关系编码 / 名称 |
| relationship_other | varchar(128) | 否 | 关系其他描述 |
| name | varchar(128) | 否 | 联系人姓名 |
| telecom | varchar(64) | 否 | 联系电话 |
| current_address | varchar(255) | 否 | 现住地址全称 |
| create_time / update_time | datetime | 是 / 否 | 创建 / 更新时间 |

## 3.6 conn_rhc_address 地址表

（AddressInfoDTO，对应 currentAddressInfo / workplaceAddressInfo）

| 字段 | 类型 | 是否必须 | 说明 |
| ---- | ---- | ---- | ---- |
| id | bigint | 主键 | 主键 ID |
| master_index_id | bigint | 否 | 居民主索引 ID |
| is_default | tinyint | 否 | 是否默认：1-默认，0-非默认 |
| addr_type | tinyint | 否 | 地址类型：1-当前地址，2-工作单位地址 |
| nationality_code / nationality | varchar(32) / varchar(64) | 否 | 国籍编码 / 名称 |
| province_id / province | bigint / varchar(128) | 否 | 省 ID / 名称 |
| city_id / city | bigint / varchar(128) | 否 | 市 ID / 全称 |
| county_id / county | bigint / varchar(128) | 否 | 县 ID / 名称 |
| town_id / town | bigint / varchar(128) | 否 | 镇 ID / 名称 |
| village_id / village | bigint / varchar(128) | 否 | 村 ID / 名称 |
| detail | varchar(255) | 否 | 详细地址（门牌号、楼栋等） |
| postcode | varchar(16) | 否 | 邮编 |
| create_time / update_time | datetime | 是 / 否 | 创建 / 更新时间 |

---

# 4 就诊与临床数据

## 4.1 conn_healthcare_record 就诊记录表

（对应接口 2.5）

| 字段 | 类型 | 是否必须 | 说明 |
| ---- | ---- | ---- | ---- |
| id | bigint | 主键 | 主键 ID |
| org_id | bigint | 是 | 机构 ID |
| healthcare_record_id | bigint | 是 | 诊疗记录 ID，每次就诊不同（唯一索引） |
| healthcare_record_no | varchar(64) | 是 | 诊疗记录号，每次就诊不同（唯一索引） |
| patient_id | bigint | 是 | 患者 ID |
| patient_name | varchar(128) | 是 | 患者名称 |
| gender / gender_code | varchar(16) / varchar(32) | 否 | 性别名称 / 编码（GB/T2261.1） |
| birthday | datetime | 否 | 生日 |
| age | varchar(32) | 否 | 年龄 |
| nation | varchar(64) | 否 | 民族 |
| id_card_no | varchar(32) | 否 | 身份证号（脱敏） |
| medical_card_id / medical_card_no | bigint / varchar(64) | 否 | 就诊卡 ID / 卡号 |
| medical_card_type / medical_card_type_name | varchar(64) / varchar(128) | 否 | 就诊卡类型 / 类型名称 |
| medical_record_no | varchar(64) | 否 | 病历号，患者每次就诊相同 |
| dept_id / dept_name | bigint / varchar(255) | 否 | 科室 ID / 名称 |
| area_id / area_name | bigint / varchar(255) | 否 | 病区 ID / 名称 |
| room_id / room_number | bigint / varchar(64) | 否 | 房间 ID / 房号 |
| bed_id / bed_number | bigint / varchar(64) | 否 | 床位 ID / 床号 |
| doctor_id / doctor_name | bigint / varchar(128) | 否 | 医生 ID / 姓名 |
| chief_nurse_id / chief_nurse_name | bigint / varchar(128) | 否 | 责任护士 ID / 姓名 |
| healthcare_period_id | bigint | 否 | 健康周期 ID |
| cost_category_code / cost_category_name | varchar(32) / varchar(64) | 否 | 费用类别编码（FH0320）/ 名称 |
| encounter_time | datetime | 否 | 就诊时间（入院登记/号源） |
| last_encounter_time | datetime | 否 | 最近就诊时间 |
| healthcare_record_status | varchar(32) | 否 | 诊疗状态（值域 2.6，如 FH0109.03） |
| healthcare_type | varchar(32) | 否 | 诊疗类型（FH0108：.01-门诊，.02-住院，.03-急诊，.04-留观，.05-抢救，.06-门诊体检，.07-住院体检） |
| healthcare_record_times | int | 否 | 诊疗次数 |
| hospitalization_number | int | 否 | 住院次数 |
| nursing_level | varchar(32) | 否 | 护理级别 |
| address | varchar(255) | 否 | 地址 |
| diagnosis_code / diagnosis_name | varchar(1024) / varchar(2048) | 否 | 本次所有诊断 ICD 编码 / 名称，多条用「、」分隔 |
| start_time / pre_out_hospital_date / out_hospital_settle_date | datetime | 否 | 入院 / 出院 / 结算时间 |
| pinyin / phone / remark | varchar / varchar / varchar(512) | 否 | 拼音 / 手机号 / 备注 |
| create_time / update_time | datetime | 是 / 否 | 创建 / 更新时间 |

## 4.2 conn_lab_test_report 检验报告表

（对应接口 2.6 报告头）

| 字段 | 类型 | 是否必须 | 说明 |
| ---- | ---- | ---- | ---- |
| id | bigint | 主键 | 主键 ID |
| org_id | bigint | 是 | 机构 ID |
| healthcare_record_no | varchar(64) | 是 | 就诊记录号 |
| medical_record_no | varchar(64) | 是 | 病历号 |
| healthcheck_record_no | varchar(64) | 否 | 体检号 |
| doctor_order_id | bigint | 是 | 医嘱 ID |
| apply_no | varchar(64) | 是 | 申请单号（唯一索引） |
| patient_id / patient_name | bigint / varchar(128) | 否 | 患者 ID / 姓名 |
| patient_type | varchar(32) | 是 | 患者类型（FH0108） |
| id_card_no | varchar(32) | 否 | 身份证号（脱敏） |
| sex / age | varchar(16) / varchar(32) | 否 | 性别 / 年龄 |
| dept_id / dept_name | bigint / varchar(255) | 否 | 科室 ID / 名称 |
| sample_no / sample_desc | varchar(64) / varchar(255) | 否 | 样本号 / 样本描述 |
| barcode | varchar(64) | 是 | 条码号 |
| sample_type_code / sample_type_name | varchar(32) / varchar(64) | 是 | 样本类型编码 / 名称 |
| send_user_id / send_user_name | varchar(64) / varchar(128) | 否 | 申请医生 ID / 姓名 |
| collect_time / register_time | datetime | 是 | 采样 / 登记时间 |
| abnormal_status | tinyint | 否 | 0-正常，1-异常 |
| report_user_job_number / report_user_name | varchar(64) / varchar(128) | 是 | 报告人工号 / 姓名 |
| report_time | datetime | 是 | 报告时间 |
| report_check_user_id | bigint | 是 | 报告审核人 ID |
| report_check_user_job_number / report_check_user_name | varchar(64) / varchar(128) | 是 | 审核人工号 / 姓名 |
| report_check_time | datetime | 是 | 报告审核时间 |
| report_file_path | varchar(512) | 否 | PDF 报告路径 |
| report_extension_files | text | 否 | 补充报告路径列表（JSON 数组） |
| create_time / update_time | datetime | 是 / 否 | 创建 / 更新时间 |

## 4.3 conn_lab_test_item 检验报告明细表

（对应接口 2.6 labTestItems）

| 字段 | 类型 | 是否必须 | 说明 |
| ---- | ---- | ---- | ---- |
| id | bigint | 主键 | 主键 ID |
| report_id | bigint | 是 | 关联 conn_lab_test_report.id |
| item_id | bigint | 是 | 检验项目 ID |
| lab_test_item_code | varchar(64) | 是 | 检验项目编码 |
| lab_test_item_name | varchar(128) | 是 | 检验项目名称 |
| print_seq_number | varchar(32) | 是 | 打印序号 |
| lab_test_meaning | varchar(512) | 否 | 临床意义 |
| lab_test_ref_range | varchar(255) | 否 | 参考范围 |
| lab_test_result | varchar(255) | 否 | 检验结果值 |
| lab_test_result_unit | varchar(64) | 否 | 结果单位 |
| ref_status | varchar(32) | 否 | 结果状态标识（正常/异常/偏高/偏低等） |
| lab_test_result_prev_one | varchar(255) | 否 | 前一次检验结果 |
| lab_test_result_other_date | datetime | 否 | 历史对照日期 |
| sample_desc | varchar(255) | 否 | 样本描述（血、痰、尿等） |
| sample_type_code / sample_type_name | varchar(32) / varchar(64) | 否 | 样本类型编码 / 名称 |
| collect_time / register_time | datetime | 否 | 采样 / 登记时间 |
| create_time / update_time | datetime | 是 / 否 | 创建 / 更新时间 |

## 4.4 conn_exam_apply_report 检查申请与报告表

（对应接口 2.7，出参 records 元素）

| 字段 | 类型 | 是否必须 | 说明 |
| ---- | ---- | ---- | ---- |
| id | bigint | 主键 | 主键 ID |
| org_id | bigint | 是 | 机构 ID |
| apply_id | bigint | 是 | 申请单 ID（唯一索引） |
| apply_no | varchar(64) | 是 | 申请单号（业务主键，如 X94069...）（唯一索引） |
| apply_status_code | int | 否 | 申请状态编码（如 8-已报告/已完成，2 的幂次位设计） |
| apply_status_name | varchar(64) | 否 | 申请状态名称（如 已取消/已执行/已报告） |
| apply_time | datetime | 否 | 申请时间 |
| apply_dept_id / apply_dept_name | bigint / varchar(255) | 否 | 申请科室 ID / 名称 |
| apply_doctor_job_number / apply_doctor_name | varchar(64) / varchar(128) | 否 | 申请医生工号 / 姓名 |
| execute_dept_id / execute_dept_name | bigint / varchar(255) | 否 | 执行科室 ID / 名称 |
| examination_user_name | varchar(128) | 否 | 检查医师姓名 |
| examination_time | datetime | 否 | 检查执行时间 |
| exam_type / sub_type_code / sub_type_name | varchar(32) / varchar(64) / varchar(128) | 否 | 检查大类型 / 子类型编码 / 子类型名称 |
| item_id / item_code / item_name | bigint / varchar(64) / varchar(128) | 否 | 检查项目 ID / 编码（如 JC-2022-4588）/ 名称 |
| imaging_findings | text | 否 | 影像所见（检查发现） |
| imaging_diagnosis | text | 否 | 影像诊断结论 |
| report_user_job_number / report_user_name | varchar(64) / varchar(128) | 否 | 报告人工号 / 姓名 |
| report_check_user_job_number / report_check_user_name | varchar(64) / varchar(128) | 否 | 报告审核人工号 / 姓名 |
| report_time | datetime | 否 | 报告时间 |
| report_check_time | datetime | 否 | 报告审核时间 |
| report_extension | varchar(512) | 否 | 报告扩展信息（路径/补充内容） |
| image_id | varchar(128) | 否 | PACS 影像 ID |
| healthcare_type | varchar(32) | 否 | 诊疗类型编码（FH0108，如 FH0108.02-住院） |
| healthcare_record_id / healthcare_record_no | bigint / varchar(64) | 否 | 诊疗记录 ID / 号 |
| medical_record_no | varchar(64) | 否 | 病历号 |
| patient_id / patient_name | bigint / varchar(128) | 否 | 患者 ID / 姓名 |
| campus_id / campus_name | bigint / varchar(255) | 否 | 院区 ID / 名称 |
| create_time / update_time | datetime | 是 / 否 | 创建 / 更新时间 |

> 说明：本表字段依据接口 2.7 出参 `records` 元素整理。出参外层为标准分页结构 `total / current / pages / size / records`。`apply_status_code` 采用 2 的幂次位设计（0/1-待申请、2-已预约、4-已到检、8-已报告、16-已取消等），具体取值请以平台值域文档为准。

---

# 5 表关系一览

```
conn_org 1 ──── n conn_campus 1 ──── n conn_area(病区)
   │
   └── 1 ── n conn_dept(科室)
              │
              ├── n ── m conn_staff(职工)      [conn_staff_dept 关联]
              │            └── n conn_staff_qualification(资质)
              └── n conn_bed(床位)

conn_rhc_regist(居民档案登记) 1 ── 1 conn_rhc_master_index(主索引)
   │                    ├── n conn_rhc_card(卡/证件)
   │                    ├── n conn_rhc_medical_card(医疗卡)
   │                    ├── n conn_rhc_contact(联系人)
   │                    └── n conn_rhc_address(地址)

conn_healthcare_record(就诊记录) n ── 1 conn_rhc_regist(居民)
   ├── n conn_lab_test_report(检验报告) 1 ── n conn_lab_test_item(检验明细)
   └── n conn_exam_apply_report(检查申请与报告)
```