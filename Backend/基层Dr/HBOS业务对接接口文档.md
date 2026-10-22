# HBOS 业务对接接口文档

---

**文档控制**

| 项目 | 内容 |
| ---- | ---- |
| 总页数 | - |
| 版本 | V1.0 |
| 建立日期 | - |
| 编制人 | - |
| 审核人 | - |
| 批准人 | - |
| 批准日期 | - |
| 保管人 | - |
| 存放位置 | - |

---

# 1 通用说明

## 1.1 基础约定

- 数据格式：请求与响应均为 `application/json`。
- 通信协议：基于 HTTP 协议，通过开放平台对接，支持 API 请求及事件发布。
- 平台地址：`http://{开放平台网址}/hbos-test/open-api/gw-api/{具体服务路径}`
- 请求方式：POST。
- 请求头：携带 `App-Key`（根据不同厂商传入不同值，业务对接时分配）。
- 时间参数统一格式：`yyyy-MM-dd HH:mm:ss`。

## 1.2 统一响应结构

平台统一返回结构如下：

```json
{
  "tid": "请求标识(32位字符串)",
  "code": 200,
  "success": true,
  "message": null,
  "data": { }
}
```

| 参数 | 类型 | 是否必须 | 备注 |
| ---- | ---- | ---- | ---- |
| tid | String | 是 | 请求标识，32 位字符串；可从响应 header 获取，据此在开放平台查询本次请求调用日志 |
| success | boolean | 是 | 调用是否成功：true 成功，false 失败 |
| code | int | 是 | 错误码：200 成功，500 未知异常，600 参数校验异常，以及其他错误码 |
| message | string | 否 | 错误信息；执行失败时返回错误消息 |
| data | object | 是 | 返回数据；不同接口有区分，可以是 object、list |

> 注意：三方订阅平台事件提供的接口也需返回该结构体 `success=true`，平台才能识别为推送成功。

## 1.3 公共字段定义

| 字段名 | 字段描述 | 说明 |
| ---- | ---- | ---- |
| orgId | 机构 ID | 机构唯一编号 |
| orgName | 机构名称 | 机构名称 |
| campusId | 院区 ID | 院区唯一编号 |
| campusName | 院区名称 | 院区名称 |
| areaId | 病区 ID | 病区唯一编号 |
| areaName | 病区名称 | 病区名称 |
| deptId | 科室 ID | 科室唯一编号 |
| deptName | 科室名称 | 科室名称 |
| healthcareRecordId | 就诊记录 ID | 患者单次就诊唯一记录 ID |
| healthcareRecordNo | 就诊流水号 | 患者单次就诊唯一业务流水号 |
| medicalRecordNo | 病历号 | 患者院内卡号，机构内唯一 |
| medicalCardNo | 就诊卡号 | 患者当次就诊凭证号 |
| patientId | 患者档案 ID | 患者院内唯一编码 |

---

# 2 对接接口

## 2.1 医护职工列表查询

### 2.1.1 医护职工列表查询
- 路径：`POST /osj/hbos-thirdparty-integration/standard/common/staff/queryStaffList`
- 说明：职工列表查询（接口名称：BASE-职工列表查询）。

请求参数：

| 参数 | 类型 | 是否必须 | 备注 |
| ---- | ---- | ---- | ---- |
| orgId | Long | 是 | 机构唯一标识 |
| campusId | Long | 否 | 院区唯一编号 |
| unitIds | List\<Long\> | 否 | 组织 ID 列表，支持部门、业务组等组织 |
| relationType | Integer | 否 | 组织关系：101-默认，201-人事关系，202-业务关系 |
| searchText | String | 否 | 搜索关键字，支持工号、姓名、拼音码模糊匹配，可为空 |
| status | Integer | 否 | 状态：0-停用，1-未停用 |

返回示例（`data` 部分）：

```json
[
    {
        "orgId": 91128429,
        "personnelDeptId": 97186036,
        "personnelDeptName": "示例科室",
        "businessDeptId": 91769593,
        "businessDeptName": "示例医疗机构",
        "staffId": 948511,
        "jobNumber": "xxxx925",
        "accountNo": "xxxx963",
        "name": "示例",
        "pinyin": "LIU XIANG",
        "pinyinInitials": "lx",
        "wbm": null,
        "genderCode": "GB/T2261.1.1",
        "genderName": "男",
        "telephone": null,
        "birthday": "1990-01-01 00:00:00",
        "maritalStatusCode": "GB/T2261.2.20",
        "status": 1,
        "businessDepts": [
            { "deptId": 93871349, "deptName": "示例科", "deptCode": "1" }
        ],
        "personnelDepts": [],
        "qualifications": [
            {
                "practiceTypeCode": "GB/T8561.230",
                "practiceTypeName": "卫生技术人员(医师)",
                "professionalTitleCode": "GB/T8561.231",
                "professionalTitleName": "主任医师"
            }
        ]
    }
]
```

返回参数说明：

| 字段 | 类型 | 是否必须 | 备注 |
| ---- | ---- | ---- | ---- |
| orgId | Long | 是 | 机构唯一标识 |
| personnelDeptId | Long | 是 | 人事科室唯一标识 |
| personnelDeptName | String | 是 | 人事科室名称 |
| businessDeptId | Long | 否 | 业务科室唯一标识 |
| businessDeptName | String | 否 | 业务科室名称 |
| staffId | Long | 是 | 职工唯一标识 |
| jobNumber | String | 是 | 职工工号，职工工号唯一标识 |
| accountNo | String | 否 | 职工系统账号 |
| name | String | 是 | 职工姓名 |
| pinyin | String | 否 | 姓名全拼 |
| pinyinInitials | String | 否 | 姓名拼音首字母 |
| wbm | String | 否 | 五笔输入码 |
| genderCode | String | 是 | 性别编码，参考性别字典 |
| genderName | String | 是 | 性别描述 |
| telephone | String | 否 | 联系电话 |
| birthday | String | 是 | 生日，格式：yyyy-MM-dd HH:mm:ss |
| maritalStatusCode | String | 否 | 婚姻状况编码，采用 GB/T2261.2：.10-未婚，.20-已婚，.21-初婚，.22-再婚，.23-复婚，.30-丧偶，.40-离婚，.90-未说明的婚姻状况 |
| maritalStatusName | String | 否 | 婚姻状况名称 |
| nationCode | String | 否 | 民族编码 |
| nationName | String | 否 | 民族名称 |
| photo | String | 否 | 照片（图片链接） |
| sign | String | 否 | 签名（签名图片） |
| introduction | String | 否 | 简介 |
| status | Integer | 否 | 状态：0-停用，1-启用 |
| businessDepts | List\<Object\> | 否 | 业务科室集合，元素为 DeptInfo 对象（deptId / deptCode / deptName / isLogin），无匹配时为空数组 |
| personnelDepts | List\<Object\> | 否 | 人事科室集合，元素为 DeptInfo 对象（deptId / deptCode / deptName / isLogin），无匹配时为空数组 |
| qualifications | List\<Object\> | 否 | 执业信息列表，元素为 Qualifications 对象（见下方嵌套定义），无匹配时为空数组 |

**嵌套对象 `DeptInfo`（科室对象字段定义）**——用于 `businessDepts`、`personnelDepts` 列表：

| 字段 | 类型 | 是否必须 | 备注 |
| ---- | ---- | ---- | ---- |
| deptId | Long | 是 | 科室 ID |
| deptCode | String | 否 | 科室编码 |
| deptName | String | 否 | 科室名称 |
| isLogin | Integer | 否 | 是否作为登录部门：1-是，0-否 |

**嵌套对象 `Qualifications`（执业信息）**——用于 `qualifications` 列表：

| 字段 | 类型 | 是否必须 | 备注 |
| ---- | ---- | ---- | ---- |
| practiceTypeCode | String | 是 | 执业类型编码（GB/T8561 执业类型编码，如卫生技术人员(医师)/卫生技术人员(护理)） |
| practiceTypeName | String | 是 | 执业类型名称 |
| professionalTitleCode | String | 否 | 职称编码（字典：主任医师、主任护师、副主任护师等） |
| professionalTitleName | String | 否 | 职称名称 |
| qualificationPic | String | 否 | 职称图片 |
| practiceCertNo | String | 否 | 执业证书编号 |

> 说明：`data` 为职工对象数组；同一职工可能同时具有多条不同专业的 `qualifications`，多个科室归属 `businessDepts`/`personnelDepts`；`isLogin` 用于标识职工是否以该科室作为登录科室。

## 2.2 科室列表查询服务

### 2.2.1 部门（科室/病区）列表查询
- 路径：`POST /osj/hbos-thirdparty-integration/standard/common/dept/queryDeptList`
- 说明：部门（科室/病区）列表查询（接口名称：BASE-部门列表查询）。

请求参数：

| 参数 | 类型 | 是否必须 | 备注 |
| ---- | ---- | ---- | ---- |
| orgId | Long | 是 | 机构唯一标识 |
| campusIds | List\<Long\> | 否 | 院区 ID 列表 |
| subjectCode | String | 否 | 国际学科，字典 code |
| orgSubjectCode | String | 否 | 机构学科，字典 code |
| searchText | String | 否 | 关键字查询，科室名称模糊匹配 |
| deptDimensions | List\<Integer\> | 否 | 科室维度：201-人事，202-业务；不传-全部 |
| serviceScopes | List\<String\> | 否 | 服务范围，枚举值见下表 |
| inpatientArea | Integer | 否 | 是否病区：0-不是，1-是 |
| status | Integer | 否 | 状态：0-停用，1-未停用；不传-全部 |
| deptType | String | 否 | 部门类型：FH0132.01-医疗，FH0132.02-教学，FH0132.03-科研，FH0132.04-行政，FH0132.05-后勤，FH0132.06-其他 |

`serviceScopes` 服务范围枚举：

| 值 | 说明 |
| ---- | ---- |
| emergency | 急诊 |
| medicalTechnology | 医技 |
| outpatient | 门诊 |
| hospitalization | 住院 |
| physicalExamination | 体检 |
| operation | 手术 |
| drugRoom | 药房 |
| drugWarehouse | 药库 |
| materialWarehouse | 材料库 |
| rescue | 抢救 |
| observation | 留观 |
| venousConfig | 静配 |

返回示例（`data` 部分）：

```json
[
    {
        "deptTypeName": "医疗",
        "deptName": "示例科",
        "pinyinInitials": "mzz",
        "deptId": 93871349,
        "campusId": 95910572,
        "deptTypeCode": "FH0132.01",
        "orgId": 91128429,
        "pinyin": "MEN ZHEN ZU",
        "inpatientArea": 0,
        "serviceScopes": [
            "outpatient",
            "hospitalization"
        ],
        "status": 1
    },
    {
        "deptTypeName": "行政",
        "deptName": "示例科室",
        "pinyinInitials": "hqbm",
        "deptId": 96469005,
        "campusId": 95910572,
        "deptTypeCode": "FH0132.04",
        "orgId": 91128429,
        "pinyin": "HOU QIN BU MEN",
        "inpatientArea": 0,
        "status": 1
    }
]
```

返回参数说明：

| 字段 | 类型 | 是否必须 | 备注 |
| ---- | ---- | ---- | ---- |
| orgId | Long | 是 | 机构唯一标识 |
| campusId | Long | 否 | 院区 ID |
| deptId | String | 是 | 科室唯一标识 |
| deptName | String | 是 | 科室名称 |
| parentDeptId | String | 否 | 上级科室 ID |
| deptTypeCode | String | 是 | 科室类型编码，取值同请求参数 deptType |
| deptTypeName | String | 是 | 科室类型名称，如临床、后勤、行政、科研等 |
| subjectCode | String | 否 | 国际学科，字典 code |
| subjectName | String | 否 | 国际学科名称 |
| orgSubjectCode | String | 否 | 机构学科，字典 code |
| orgSubjectName | String | 否 | 机构学科名称 |
| parentBusinessDeptId | Long | 否 | 父级业务部门 ID |
| parentBusinessName | String | 否 | 父级业务部门名称 |
| pinyin | String | 否 | 拼音 |
| pinyinInitials | String | 否 | 拼音首字母 |
| wbm | String | 否 | 五笔 |
| inpatientArea | Integer | 否 | 是否病区：0-不是，1-是 |
| serviceScopes | List\<String\> | 否 | 服务范围，枚举值同请求参数 serviceScopes |
| status | Integer | 是 | 启用状态：0-停用，1-启用 |

> 说明：`data` 为科室对象数组；同一科室可对应多个 `serviceScopes` 服务范围；`parentDeptId` 用于体现科室上下级（树形）结构。

## 2.3 床位信息查询

### 2.3.1 床位信息查询
- 路径：`POST /hbos-thirdparty-integration/standard/common/ncc/queryBedInfoList`
- 说明：床位信息查询（接口名称：床位信息查询）。

请求参数：

| 参数 | 类型 | 是否必须 | 备注 |
| ---- | ---- | ---- | ---- |
| orgId | Long | 是 | 机构唯一标识 |
| campusId | Long | 否 | 院区 ID |
| bedNumber | String | 否 | 床位号 |
| isEffective | Integer | 否 | 是否有效：1-有效，2-失效 |
| current | Long | 是 | 当前页码，默认 1 |
| size | Long | 是 | 每页数量，默认 10 |

返回示例（`data` 部分）：

```json
{
    "current": 1,
    "size": 2,
    "total": 1829,
    "pages": 915,
    "records": [
        {
            "id": 1,
            "orgId": 95512920,
            "campusId": 97098471,
            "code": "001",
            "bedNumber": "001",
            "areaId": 91527588,
            "areaName": "示例科室区域",
            "deptId": null,
            "deptName": null,
            "roomNo": "示例科室",
            "bedProperty": 1,
            "bedTypeId": 9253584970801314013,
            "bedTypeName": "示例床位类型",
            "bedRegionId": 9804806467478235303,
            "bedRegionName": "示例科室",
            "isEffective": 1
        }
    ]
}
```

返回参数说明：

`data` 为分页对象，字段如下：

| 字段 | 类型 | 是否必须 | 备注 |
| ---- | ---- | ---- | ---- |
| current | long | 是 | 当前页码，默认 1 |
| total | long | 是 | 记录总数 |
| pages | long | 是 | 总页数 |
| size | long | 是 | 每页数量，默认 10 |
| records | List | 否 | 床位信息记录列表 |

`records` 元素（床位信息，`HisBedInfoQueryThirdDTO`）字段如下：

| 字段 | 类型 | 是否必须 | 备注 |
| ---- | ---- | ---- | ---- |
| orgId | long | 是 | 机构 ID |
| campusId | long | 是 | 院区 ID |
| code | String | 是 | 床位唯一编码 |
| bedNumber | String | 是 | 床位号 |
| areaId | long | 是 | 病区 ID |
| areaName | String | 是 | 病区名称 |
| deptId | long | 是 | 科室 ID |
| deptName | String | 是 | 科室名称 |
| roomNo | String | 否 | 房间号 |
| bedProperty | String | 否 | 床位属性：1-普通床，2-新生儿虚拟床，3-通用虚拟床（血透虚拟床） |
| bedTypeId | long | 否 | 床位类型 ID |
| bedTypeName | String | 否 | 床位类型 |
| bedRegionId | long | 否 | 床位区域 ID |
| bedRegionName | String | 否 | 床位区域名称 |
| isEffective | Integer | 否 | 是否有效：1-有效，2-失效 |

> 说明：该接口为分页查询；`deptId`、`deptName` 虽标注必填，但示例中可能为 null（未绑定科室）；ID 字段较大（分布式 ID），前端建议按字符串处理避免精度丢失。

## 2.4 患者档案信息查询

### 2.4.1 患者档案信息查询
- 路径：`POST /osj/hbos-thirdparty-integration/standard/common/rhc/queryFull`
- 说明：查询居民档案信息（接口名称：患者档案信息查询）。

请求参数：

```json
{
    "orgId": 91128429,
    "medicalRecordNo": "",
    "patientId": "",
    "idCardNo": "",
    "cardTypeCode": "",
    "residentCardNo": ""
}
```

| 参数 | 类型 | 是否必须 | 备注 |
| ---- | ---- | ---- | ---- |
| orgId | Long | 是 | 机构唯一标识 |
| medicalRecordNo | String | 否 | 病历号查询方式 |
| patientId | String | 否 | 患者 ID 查询方式 |
| idCardNo | String | 否 | 居民身份证号查询方式 |
| residentCardNo | String | 否 | 卡号查询方式（居民卡 / 院内就诊卡 / 体检卡） |
| cardTypeCode | String | 否 | 证件类型编码，固定 2 位，配合证件类卡号使用 |

> 说明：`medicalRecordNo`、`patientId`、`idCardNo`、`residentCardNo` 四种患者标识**四者必传其一**，用于确定查询对象。

返回示例（`data` 部分，`data` 为居民档案完整详情对象 `ResidentOrgRegistInfoDTO`）：

```json
{
    "featureParam": null,
    "medicalRecordNo": "91132183",
    "residentMedicalCardInfos": [],
    "org": "示例医疗机构",
    "registerTime": "2020-01-01 00:00:00",
    "patientId": "9303",
    "registrantId": 9202,
    "remark": null,
    "registrant": "门诊刘医师",
    "residentMasterIndex": {
        "gender": "女",
        "name": "示例人",
        "birthDate": "1900-01-01 00:00:00",
        "age": "2岁5月",
        "status": 1,
        "idCardNo": null
    },
    "residentCardInfos": [
        {
            "id": 9254,
            "cardId": 9,
            "masterIndexId": 9404,
            "cardName": "居民户口簿",
            "cardType": "居民户口簿",
            "cardTypeCode": "CT/JM0001_1.02",
            "cardAlias": "居民户口簿",
            "residentCardNo": "947623650945273822",
            "residentName": "示例人",
            "restrictions": "GB/T2659.156",
            "restrictionsDesc": "中国",
            "status": 1,
            "createTime": "2020-01-01 00:00:00",
            "updateTime": "2020-01-01 00:00:00"
        }
    ],
    "residentCertificateCardInfos": [],
    "residentContactInfos": [],
    "workplaceAddressInfo": null,
    "currentAddressInfo": null,
    "idCardNo": null,
    "educationCode": null,
    "marriage": null,
    "marriageCode": null
}
```

返回参数说明（`data` 主要字段）：

| 字段 | 类型 | 是否必须 | 备注 |
| ---- | ---- | ---- | ---- |
| orgId | Long | 是 | 机构唯一标识 |
| org | String | 是 | 机构名称 |
| patientId | String | 是 | 患者 ID |
| medicalRecordNo | String | 是 | 病历号 |
| registerTime | String | 否 | 登记时间，格式：yyyy-MM-dd HH:mm:ss |
| registrantId | String | 否 | 登记人 ID |
| registrant | String | 否 | 登记人姓名 |
| remark | String | 否 | 备注 |
| status | Integer | 否 | 状态：0-停用，1-未停用 |
| residentMasterIndex | Object | 否 | 居民主索引：含 name 姓名、gender/genderCode 性别、birthDate 出生日期、age 年龄、nation 民族、education 学历、marriage 婚姻等基本信息 |
| residentCardInfos | List\<Object\> | 否 | 证件信息列表，元素含 cardTypeCode 证件类型编码、cardName 证件名称、residentCardNo 证件号、residentName 姓名、status 状态等 |
| residentCertificateCardInfos | List\<Object\> | 否 | 证件档案列表 |
| residentMedicalCardInfos | List\<Object\> | 否 | 医疗卡信息列表 |
| residentContactInfos | List\<Object\> | 否 | 联系方式列表 |
| currentAddressInfo | Object | 否 | 现住址信息 |
| workplaceAddressInfo | Object | 否 | 工作单位地址信息 |
| idCardNo | String | 否 | 居民身份证号 |
| educationCode | String | 否 | 学历编码 |
| marriage / marriageCode | Object / String | 否 | 婚姻状况及其编码 |
| featureParam | Object | 否 | 扩展特征参数 |

**嵌套对象 `AddressInfoDTO`（地址信息）**——用于 `registerAddressInfo`、`currentAddressInfo`、`workplaceAddressInfo`：

| 字段 | 类型 | 是否必须 | 备注 |
| ---- | ---- | ---- | ---- |
| id | Long | 否 | 主键 ID |
| isDefault | Integer | 否 | 是否默认：1-默认，0-非默认 |
| nationalityCode | String | 否 | 国籍编码 |
| nationality | String | 否 | 国籍名称 |
| provinceId / province | Long / String | 否 | 省 ID / 省名称 |
| cityId / city | Long / String | 否 | 市 ID / 市全称 |
| countyId / county | Long / String | 否 | 县 ID / 县名称 |
| townId / town | Long / String | 否 | 镇 ID / 镇名称 |
| villageId / village | Long / String | 否 | 村 ID / 村名称 |
| detail | String | 否 | 地址详细信息（门牌号、楼栋等） |
| postcode | String | 否 | 邮编 |

> 说明：`registerAddressInfo`（户籍地址信息）已标注「合并停用」，实际请使用 `currentAddressInfo`（当前地址信息）。地址采用「省-市-县-镇-村」行政层级 + `detail` 详细地址结构。

**嵌套对象 `ResidentContactInfoDTO`（居民联系人信息）**——用于 `residentContactInfos` 列表：

| 字段 | 类型 | 是否必须 | 备注 |
| ---- | ---- | ---- | ---- |
| id | Long | 否 | 主键 ID |
| masterIndexId | Long | 否 | 居民主索引 ID |
| residentName | String | 否 | 居民姓名 |
| relationshipCode | String | 否 | 关系编码 |
| relationship | String | 否 | 关系名称 |
| relationshipOther | String | 否 | 关系其他描述 |
| name | String | 否 | 联系人姓名 |
| telecom | String | 否 | 联系电话 |
| currentAddress | String | 否 | 现住地址全称 |

**嵌套对象 `ResidentCardInfoDTO`（居民卡/证件信息）**——用于 `residentCardInfos`、`residentCertificateCardInfos` 列表：

| 字段 | 类型 | 是否必须 | 备注 |
| ---- | ---- | ---- | ---- |
| id | Long | 否 | 主键 ID |
| cardId | Long | 否 | 卡 ID |
| masterIndexId | Long | 否 | 居民主索引 ID |
| residentName | String | 否 | 居民姓名 |
| residentCardNo | String | 否 | 居民卡号 |
| cardName | String | 否 | 卡名称 |
| cardAlias | String | 否 | 卡别名 |
| cardTypeCode | String | 否 | 证件类型编码（如 CT/JM0001_1.02） |
| cardType | String | 否 | 证件类型描述 |
| restrictions | String | 否 | 限制条件（区域性限制代码，如 GB/T2659.156） |
| restrictionsDesc | String | 否 | 限制条件描述 |
| status | Integer | 否 | 状态：0-停用，1-启用，2-合并停用 |
| createTime / updateTime | String | 否 | 创建 / 更新时间，格式：yyyy-MM-dd HH:mm:ss |
| featureParam | Map | 否 | 扩展参数 |

**嵌套对象 `TelecomInfoDTO`（联系信息）**——用于联系方式：

| 字段 | 类型 | 是否必须 | 备注 |
| ---- | ---- | ---- | ---- |
| typeCode | String | 否 | 类型编码（联系方式类型，参考字典 1.5） |
| telecom | String | 否 | 联系方式 |

**嵌套对象 `ResidentMasterIndexDTO`（居民主索引）**——用于 `residentMasterIndex`，集中返回居民的全量基础档案信息：

| 字段 | 类型 | 是否必须 | 备注 |
| ---- | ---- | ---- | ---- |
| id | Long | 否 | 主键 ID |
| name | String | 否 | 姓名 |
| namePinyin | String | 否 | 姓名拼音 |
| genderCode / gender | String | 否 | 性别编码（GB/T 2261.1）及性别描述 |
| birthDate | LocalDateTime | 否 | 出生日期 |
| age | String | 否 | 年龄（如「2岁5月」） |
| idCardNo | String | 否 | 居民身份证号 |
| telecom | String | 否 | 本人联系电话 |
| telecomInfoList | List\<TelecomInfoDTO\> | 否 | 联系方式列表 |
| languageCode / language | String | 否 | 语言编码多选 / 语言描述多选 |
| nationalityCode / nationality | String | 否 | 国籍编码 / 国籍描述 |
| nationalityOther | String | 否 | 国籍其他（自由文本） |
| nationCode / nation | String | 否 | 民族编码 / 民族描述 |
| nationOther | String | 否 | 民族其他（自由文本） |
| nativePlaceId / nativePlace | Long / String | 否 | 籍贯行政区划 ID / 籍贯行政区划全称 |
| registerAddressId / registerAddress | Long / String | 否 | 户籍地址 ID / 户籍地址全称 |
| currentAddressId / currentAddress | Long / String | 否 | 现住地址 ID / 现住地址全称 |
| workplace | String | 否 | 工作单位 |
| workplaceTelecom | String | 否 | 工作单位联系电话 |
| workplaceAddressId / workplaceAddress | Long / String | 否 | 工作单位地址 ID / 工作单位地址全称 |
| educationCode / education | String | 否 | 文化程度编码 / 文化程度 |
| occupationTypeCode / occupationType | String | 否 | 职业类型编码 / 职业类型描述 |
| marriageCode / marriage | String | 否 | 婚姻状况编码（GB/T 2261.2）及描述 |
| medicalCostsPayModeCode / medicalCostsPayMode | String | 否 | 医疗费用支付方式编码多选 / 描述多选 |
| enExhaustAirCode / enExhaustAir | String | 否 | 生活环境-厨房排风设施编码 / 描述 |
| enFuelTypeCode / enFuelType / enFuelTypeOther | String | 否 | 生活环境-燃料类型编码 / 描述 / 其他 |
| status | Integer | 否 | 状态：1-使用中，2-已停用，3-已注销 |

> 说明：`data` 为单条居民档案详情对象；该接口出参的地址、附件、民族、国籍等字段均以「编码＋描述 / ID＋全称」的扁平键值形式返回，未使用独立的 `AddressInfoDTO` 嵌套对象；`status` 枚举：1-使用中，2-已停用；性别、民族、学历、婚姻等采用 GB/T 国家标准的编码+名称结构返回；`residentMasterIndex`、`residentContactInfos`、`residentCardInfos`、`residentMedicalCardInfos` 等为嵌套对象/数组。

## 2.5 就诊记录信息查询

### 2.5.1 就诊记录信息查询
- 路径：`POST /osj/hbos-thirdparty-integration/standard/common/healthcareRecord/dtcQueryHealthcareRecordList`
- 说明：查询患者诊疗信息（接口名称：查询患者诊疗信息），返回诊疗记录列表。

请求参数：

```json
{
    "orgId": 95123444,
    "campusId": null,
    "patientName": null,
    "idCardNo": null,
    "areaId": null,
    "deptId": null,
    "deptIdList": null,
    "healthcareRecordNo": "93931099044809",
    "healthcareRecordNoList": null,
    "medicalRecordNo": null,
    "healthcareRecordId": null,
    "idList": null,
    "medicalCardNo": null,
    "startHeadTime": null,
    "startTailTime": null
}
```

| 参数 | 类型 | 是否必须 | 备注 |
| ---- | ---- | ---- | ---- |
| orgId | Long | 是 | 机构唯一标识 |
| campusId | Long | 否 | 院区 ID |
| patientName | String | 否 | 患者姓名 |
| idCardNo | String | 否 | 身份证号 |
| areaId | Long | 否 | 病区 ID |
| deptId / deptIdList | Long / List\<Long\> | 否 | 科室 ID / 科室 ID 集合 |
| medicalCardNo | String | 否 | 就诊卡号 |
| healthcareRecordNo | String | 是（三者必填其一） | 诊疗记录号，患者每次就诊都不相同 |
| healthcareRecordNoList | List\<String\> | 是（三者必填其一） | 诊疗记录号集合 |
| healthcareRecordId | Long | 否 | 诊疗记录 ID |
| idList | List\<Long\> | 是（三者必填其一） | 诊疗记录 ID 集合 |
| medicalRecordNo | String | 否 | 病历号，患者每次就诊都相同 |
| startHeadTime / startTailTime | String | 否 | 入院（开始/结束）时间，格式：yyyy-MM-dd |
| preOutHospitalHeadDate / preOutHospitalTailDate | String | 否 | 出院（开始/结束）时间，格式：yyyy-MM-dd |
| encounterTimeStart / encounterTimeEnd | String | 否 | 就诊（开始/结束）时间，格式：yyyy-MM-dd（门诊取号源时间，住院取入院登记时间） |
| outHospitalSettleHeadDate / outHospitalSettleTailDate | String | 否 | 出院结算（开始/结束）时间，格式：yyyy-MM-dd |
| healthcareRecordTypeList | List\<String\> | 否 | 诊疗类型列表 |
| statusList | List\<String\> | 否 | 诊疗状态（参考值域文档 2.6） |
| recently | Boolean | 否 | 是否取最近一次诊疗信息，默认 false |

`healthcareRecordTypeList` 诊疗类型枚举（FH0108）：

| 值 | 说明 |
| ---- | ---- |
| FH0108.01 | 门诊 |
| FH0108.02 | 住院 |
| FH0108.03 | 急诊 |
| FH0108.04 | 留观 |
| FH0108.05 | 抢救 |
| FH0108.06 | 门诊体检 |
| FH0108.07 | 住院体检 |

> 说明：`healthcareRecordNo`、`healthcareRecordNoList`、`idList` 三者**必填其一且互斥**，用于确定查询对象；`recently=true` 时仅返回最近一次诊疗记录，否则返回全部匹配记录。

返回示例（`data` 部分，`data` 为诊疗记录列表）：

```json
{
    "healthcareRecordId": 91842753890465,
    "healthcareRecordNo": "93931099044809",
    "patientId": 960052,
    "patientName": "示例人员",
    "gender": "女",
    "genderCode": "GB/T2261.1.2",
    "birthday": "1990-01-01 00:00:00",
    "age": "22岁",
    "nation": null,
    "idCardNo": "928755259064015979",
    "medicalCardId": 9,
    "medicalCardType": "居民身份证",
    "medicalCardTypeName": null,
    "medicalCardNo": "990957594536770404",
    "medicalRecordNo": "96109882",
    "deptId": 96257940,
    "deptName": "示例科",
    "areaId": null,
    "areaName": null,
    "roomId": null,
    "roomNumber": null,
    "bedId": null,
    "bedNumber": null,
    "doctorId": 99543,
    "doctorName": "示例人员",
    "chiefNurseId": null,
    "chiefNurseName": "示例科",
    "healthcarePeriodId": 957710281,
    "costCategoryCode": "FH0320.01",
    "costCategoryName": "自费",
    "encounterTime": "2020-01-01 00:00:00",
    "lastEncounterTime": "2020-01-01 00:00:00",
    "healthcareRecordStatus": "FH0109.03",
    "healthcareType": "FH0108.01",
    "healthcareRecordTimes": 2,
    "hospitalizationNumber": null,
    "nursingLevel": null,
    "address": null,
    "diagnosisCode": null,
    "diagnosisName": null,
    "orgId": 95512641
}
```

返回参数说明（`data` 主要字段，`data` 为列表元素对象）：

| 字段 | 类型 | 是否必须 | 备注 |
| ---- | ---- | ---- | ---- |
| orgId | Long | 是 | 机构 ID |
| healthcareRecordId | Long | 是 | 诊疗记录 ID，患者每次就诊都不相同 |
| healthcareRecordNo | String | 是 | 诊疗记录号，患者每次就诊都不相同 |
| patientId | Long | 是 | 患者 ID，同一患者多次就诊相同 |
| patientName | String | 是 | 患者名称 |
| pinyin | String | 否 | 姓名拼音 |
| gender / genderCode | String | 否 | 性别描述 / 性别编码（GB/T 2261.1：.0-未知，.1-男，.2-女，.9-未说明的性别） |
| birthday | String | 否 | 生日，格式：yyyy-MM-dd HH:mm:ss |
| age | String | 否 | 年龄（如「22岁」） |
| nation | String | 否 | 民族 |
| idCardNo | String | 否 | 身份证号 |
| address | String | 否 | 患者地址 |
| phone | String | 否 | 手机号 |
| nativePlace | String | 否 | 籍贯 |
| educationCode / education | String | 否 | 文化程度编码 / 文化程度描述 |
| marriageCode / marriage | String | 否 | 婚姻状况编码 / 婚姻状况描述 |
| occupationTypeCode / occupationType | String | 否 | 职业编码 / 职业描述 |
| medicalCardId / medicalCardNo | Long / String | 否 | 就诊卡 ID / 就诊卡号 |
| medicalCardType / medicalCardTypeName | String | 否 | 就诊卡类型 / 就诊卡类型名称 |
| medicalRecordNo | String | 否 | 病历号，患者每次就诊都相同 |
| deptId / deptName | Long / String | 否 | 科室 ID / 科室名称 |
| areaId / areaName | Long / String | 否 | 病区 ID / 病区名称 |
| roomId / roomNumber | Long / String | 否 | 房间 ID / 房号 |
| bedId / bedNumber | Long / String | 否 | 床位 ID / 床号 |
| doctorId / doctorName | Long / String | 否 | 医生 ID / 医生姓名 |
| chiefNurseId / chiefNurseName | Long / String | 否 | 责任护士 ID / 责任护士姓名 |
| healthcarePeriodId | Long | 否 | 诊疗周期 ID |
| lastEncounterTime | String | 否 | 最新一次入院时间（与创建时间一致），格式：yyyy-MM-dd HH:mm:ss |
| encounterTime | String | 否 | 入院登记时间，格式：yyyy-MM-dd HH:mm:ss |
| startTime | String | 否 | 入院时间，格式：yyyy-MM-dd HH:mm:ss |
| preOutHospitalDate | String | 否 | 出院时间，格式：yyyy-MM-dd HH:mm:ss |
| outHospitalSettleDate | String | 否 | 结算时间，格式：yyyy-MM-dd HH:mm:ss |
| costCategoryCode / costCategoryName | String | 否 | 费用类别编码 / 名称 |
| costNatureCode / costNatureName | String | 否 | 费用性质编码 / 名称 |
| neonatal | Boolean | 否 | 是否新生儿 |
| furtherConsultationStatus | Integer | 否 | 复诊状态：0-否，1-是 |
| healthcareRecordStatus | String | 否 | 诊疗状态（参考值域文档 2.6，如 FH0109.03） |
| healthcareType | String | 否 | 诊疗类型（FH0108 枚举，同请求参数 healthcareRecordTypeList） |
| healthcareRecordTimes | Integer | 否 | 诊疗次数 |
| hospitalizationNumber | Integer | 否 | 住院次数 |
| nursingLevel | String | 否 | 护理级别 |
| diagnosisCode / diagnosisName | String | 否 | 本次就诊所有诊断的 ICD 编码 / 诊断名称，多个用「、」分隔 |
| residentContactInfos | List\<ResidentContactInfoDTO\> | 否 | 联系人列表（见嵌套定义） |
| diagnosisList | List\<DiagnosisInfo\> | 否 | 诊断列表（见嵌套定义） |
| medicalRecordDataList | List\<MedicalRecordDataInfo\> | 否 | 病历信息列表（见嵌套定义） |

**嵌套对象 `ResidentContactInfoDTO`（联系人）**——用于 `residentContactInfos`：

| 字段 | 类型 | 是否必须 | 备注 |
| ---- | ---- | ---- | ---- |
| name | String | 否 | 联系人姓名 |
| relationshipCode / relationship | String | 否 | 联系人关系编码 / 关系名称 |
| telecom | String | 否 | 联系人联系方式 |
| currentAddress | String | 否 | 联系地址 |

**嵌套对象 `DiagnosisInfo`（诊断）**——用于 `diagnosisList`：

| 字段 | 类型 | 是否必须 | 备注 |
| ---- | ---- | ---- | ---- |
| diagnosisDictCode | String | 否 | 诊断 ICD 编码 |
| diagnosisDictName | String | 否 | 诊断名称 |
| diagnosisCategory | String | 否 | 诊断类别（FH0176） |
| recordType | Integer | 否 | 诊断类型：1-中医诊断，2-西医诊断 |
| isMainDiagnosis | Integer | 否 | 是否主诊断：1-主诊断，0-非主诊断 |

**嵌套对象 `MedicalRecordDataInfo`（病历信息）**——用于 `medicalRecordDataList`：

| 字段 | 类型 | 是否必须 | 备注 |
| ---- | ---- | ---- | ---- |
| code | String | 否 | 病历数据元编码（DE 标准，如主诉 DE04.01.119.00、现病史 DE02.10.071.00、既往史 DE02.10.099.00、体格检查 DE04.10.258.00、个人史 DE02.10.097.00 等） |
| content | String | 否 | 病历内容 |

`diagnosisCategory` 诊断类别枚举（FH0176）：

| 值 | 说明 |
| ---- | ---- |
| FH0176.01 | 入院诊断 |
| FH0176.02 | 初步诊断 |
| FH0176.03 | 修正诊断 |
| FH0176.04 | 补充诊断 |
| FH0176.05 | 出院诊断 |
| FH0176.06 | 主治诊断 |

> 说明：`data` 为诊疗记录对象数组；`healthcareRecordId/healthcareRecordNo` 每次就诊不同，`patientId`、`medicalRecordNo` 同一患者相同，用于患者级关联；`genderCode`、`medicalCardType`、`healthcareType`、`costCategoryCode` 等采用 FH / GB/T 标准编码；诊断存在多条时，`diagnosisCode`、`diagnosisName` 用「、」分隔按序对应，结构化的多条诊断以 `diagnosisList` 返回。

## 2.6 检验报告信息查询

### 2.6.1 检验报告信息查询
- 路径：`POST /osj/hbos-thirdparty-integration/standard/common/labTestReport/dtcQueryLabTestReportList`
- 说明：查询检验报告信息（接口名称：查询检验报告信息），返回检验报告列表，每条报告包含报告基本信息和检验项目明细 `labTestItems`。

请求参数：

| 参数 | 类型 | 是否必须 | 备注 |
| ---- | ---- | ---- | ---- |
| orgId | Long | 是 | 机构唯一标识 |
| doctorOrderId | Long | 否 | 医嘱 ID |
| applyNo | String | 否 | 申请单号 |
| healthcareRecordNo | String | 是 | 就诊记录号（住院号/门诊号） |
| healthcheckRecordNo | String | 否 | 体检号 |
| medicalRecordNo | String | 是 | 病历号（多次就诊只对应一个） |
| patientName | String | 否 | 患者姓名 |
| patientType | String | 是 | 患者类型（FH0108 枚举：FH0108.01-门诊，.02-住院，.03-急诊，.04-留观，.05-抢救） |
| idCardNo | String | 否 | 身份证号 |
| current | Long | 否 | 当前页码，默认 1 |
| size | Long | 否 | 每页数量，默认 10 |

> 说明：该接口支持分页；患者标识类参数为查询条件，要求 `healthcareRecordNo`、`medicalRecordNo` 必填。

返回示例（`data` 部分，`data.records` 为报告列表，每项含 `labTestItems`）：

```json
{
    "orgId": 95123444,
    "doctorOrderId": 9034,
    "applyNo": "X9823371631679860240498",
    "healthcareRecordNo": "970865644252378982124355",
    "medicalRecordNo": "913149448",
    "healthcheckRecordNo": null,
    "patientName": "示例人",
    "patientType": "FH0108.01",
    "idCardNo": "920448428638941122",
    "sex": "女",
    "age": "22岁",
    "deptId": 8979,
    "deptName": "示例科",
    "sampleNo": "9453",
    "sampleDesc": "示例样本",
    "barcode": "972362681",
    "sampleTypeCode": "02",
    "sampleTypeName": "血",
    "sendUserId": "908441757",
    "sendUserName": "示例医师",
    "collectTime": "2020-01-01 00:00:00",
    "registerTime": "2020-01-01 00:00:00",
    "abnormalStatus": 0,
    "reportUserJobNumber": "xxxx",
    "reportUserName": "示例报告人",
    "reportTime": "2020-01-01 00:00:00",
    "reportCheckUserId": 9027,
    "reportCheckUserJobNumber": "xxxx",
    "reportCheckUserName": "示例审核人",
    "reportCheckTime": "2020-01-01 00:00:00",
    "reportFilePath": null,
    "reportExtensionFiles": [],
    "labTestItems": [
        {
            "itemId": 9246,
            "labTestItemCode": "24HNL",
            "labTestItemName": "示例检验项目",
            "printSeqNumber": "1",
            "labTestMeaning": null,
            "labTestRefRange": "示例参考范围",
            "labTestResult": "示例结果值",
            "labTestResultUnit": null,
            "refStatus": "正常",
            "labTestResultPrevOne": null,
            "labTestResultOtherDate": "2020-01-01 00:00:00",
            "sampleDesc": null,
            "collectTime": "2020-01-01 00:00:00",
            "registerTime": "2020-01-01 00:00:00"
        }
    ]
}
```

返回参数说明（`data`，`data` 为报告对象，`data.labTestItems` 为检验项目明细数组）：

报告基本信息字段：

| 字段 | 类型 | 是否必须 | 备注 |
| ---- | ---- | ---- | ---- |
| orgId | Long | 是 | 机构 ID |
| doctorOrderId | Long | 是 | 医嘱 ID |
| applyNo | String | 是 | 申请单号 |
| healthcareRecordNo | String | 是 | 就诊记录号（住院号/门诊号），每次就诊不同 |
| medicalRecordNo | String | 是 | 病历号，多次就诊只有一个 ID 号 |
| healthcheckRecordNo | String | 否 | 体检号 |
| patientId / patientName | Long / String | 否 | 患者 ID / 患者姓名 |
| patientType | String | 是 | 患者类型（FH0108.01-门诊，.02-住院，.03-急诊，.04-留观，.05-抢救） |
| idCardNo | String | 否 | 身份证号 |
| sex / age | String | 否 | 性别 / 年龄 |
| deptId / deptName | Long / String | 否 | 科室 ID / 科室名称 |
| sampleNo / sampleDesc | String | 否 | 样本号 / 样本描述 |
| sampleName | String | 否 | 样本名称（如：临检-血常规） |
| barcode | String | 是 | 条码号 |
| sampleTypeCode / sampleTypeName | String | 是 | 样本类型编码 / 样本类型名称 |
| sendUserId / sendUserName | Long / String | 否 | 申请医生 ID / 申请医生姓名 |
| collectTime | String | 是 | 采样时间，格式：yyyy-MM-dd HH:mm:ss |
| registerTime | String | 是 | 登记时间，格式：yyyy-MM-dd HH:mm:ss |
| abnormalStatus | Integer | 否 | 异常状态：0-正常，1-异常 |
| reportUserId | Long | 是 | 报告人 ID |
| reportUserJobNumber / reportUserName | String | 是 | 报告人工号 / 报告人姓名 |
| reportTime | String | 是 | 报告时间，格式：yyyy-MM-dd HH:mm:ss |
| reportCheckUserId | Long | 是 | 报告审核人 ID |
| reportCheckUserJobNumber / reportCheckUserName | String | 是 | 报告审核人工号 / 报告审核人姓名 |
| reportCheckTime | String | 是 | 报告审核时间，格式：yyyy-MM-dd HH:mm:ss |
| reportFilePath | String | 否 | PDF 报告路径 |
| reportExtensionFiles | List\<String\> | 否 | 补充报告路径列表 |

`labTestItems` 元素（检验项目明细）字段：

| 字段 | 类型 | 是否必须 | 备注 |
| ---- | ---- | ---- | ---- |
| itemId | Long | 是 | 检验项目 ID（全局唯一） |
| labTestItemCode | String | 是 | 检验项目编码 |
| labTestItemName | String | 是 | 检验项目名称 |
| printSeqNumber | String | 是 | 打印序号 |
| labTestMeaning | String | 否 | 检验项目临床意义 |
| labTestRefRange | String | 是 | 参考范围 |
| labTestResult | String | 是 | 检验结果值 |
| labTestResultOther | String | 是 | 实验结果定性 |
| labTestResultUnit | String | 是 | 检验结果单位 |
| refStatus | String | 是 | 参考状态：0-未判断，1-偏低，2-正常，3-偏高，4-阴性，5-阳性 |
| labTestResultOtherDate | String | 否 | 历史对照日期，格式：yyyy-MM-dd HH:mm:ss |
| labTestResultPrevOne | String | 否 | 上一次结果 |
| labTestResultPrevOther | String | 否 | 前一次实验结果定性 |
| labTestResultPrevOtherDate | String | 否 | 前一次实验时间，格式：yyyy-MM-dd HH:mm:ss |
| sampleName | String | 否 | 样本名称 |
| sampleTypeCode / sampleTypeName | String | 否 | 样本类型编码 / 名称（血、痰、尿等） |
| sampleDesc | String | 否 | 样本描述（血、痰、尿等） |
| abnormalStatus | Integer | 否 | 异常状态：0-正常，1-异常 |
| collectTime | String | 否 | 采样时间，格式：yyyy-MM-dd HH:mm:ss |
| registerTime | String | 否 | 登记时间，格式：yyyy-MM-dd HH:mm:ss |

> 说明：`data` 为检验报告对象（`data.records` 为报告列表），`data.labTestItems` 为报告下的检验项目明细数组；`sampleTypeName` 参考取值范围如：血、痰、尿等；`refStatus` 参考状态取值：0-未判断，1-偏低，2-正常，3-偏高，4-阴性，5-阳性；`collectTime`（采样时间）、`registerTime`（登记时间）采用 yyyy-MM-dd HH:mm:ss 格式；`abnormalStatus` 0-正常、1-异常；报告人 `reportUser*` 与审核人 `reportCheckUser*` 分开记录，满足检验报告双重审核合规要求。

## 2.7 检查报告查询

### 2.7.1 检查报告分页查询服务
- 路径：`POST /osj/hbos-thirdparty-integration/standard/common/labTest/pageExaminationApplyAndReport`
- 说明：检查报告分页查询服务（接口名称：检查报告查询），返回检查申请与报告记录。

请求参数：

```json
{
    "applyStartTime": "2020-01-01 00:00:00",
    "applyEndTime": "2020-01-01 00:00:00",
    "current": 1,
    "includeReport": true,
    "medicalRecordNo": "924627387",
    "orgId": 95123444,
    "size": 10,
    "reportStatus": 1,
    "itemTypes": ["FH0101.01.03.01", "FH0101.01.04"]
}
```

| 参数 | 类型 | 是否必须 | 备注 |
| ---- | ---- | ---- | ---- |
| orgId | Long | 是 | 机构唯一标识 |
| campusId | Long | 否 | 院区 ID |
| patientId | Long | 否 | 患者 ID |
| medicalRecordNo | String | 是（必填其一） | 病历号，一个 |
| healthcareRecordId | Long | 否 | 诊疗记录 ID |
| healthcareRecordNo | String | 否 | 诊疗记录号（住院号/门诊就诊号/就诊流水号） |
| reportStatus | Integer | 否 | 报告状态：0-未出，1-已出，2/null-全部 |
| itemTypes | Array\<String\> | 否 | 检查项目类型（FH0101 编码，见下方枚举说明），可多选 |
| itemCodes | Array\<String\> | 否 | 检查项目编码集合 |
| healthcareTypeList | List\<String\> | 否 | 诊疗类型集合（FH0108.01-门诊，.02-住院，.03-急诊，.04-留观，.05-抢救） |
| applyId | Long | 否 | 申请单 ID |
| applyNo | String | 否 | 申请单号 |
| applyStartTime | String | 否 | 申请开始时间，格式：yyyy-MM-dd HH:mm:ss |
| applyEndTime | String | 否 | 申请结束时间，格式：yyyy-MM-dd HH:mm:ss |
| reportCheckStartTime | String | 否 | 报告审核开始时间，格式：yyyy-MM-dd HH:mm:ss |
| reportCheckEndTime | String | 否 | 报告审核结束时间，格式：yyyy-MM-dd HH:mm:ss |
| includeReport | Boolean | 否 | 是否返回报告数据，默认 true |
| current | Long | 是 | 当前页码，默认 1 |
| size | Long | 是 | 每页数量，默认 10 |

> 说明：`medicalRecordNo`、`patientId`、`healthcareRecordId`、`healthcareRecordNo` 等患者/就诊标识为多选一查询方式确定查询对象；`includeReport=false` 时仅返回检查申请信息，不返回报告内容，可减少返回体大小。

`itemTypes` 检查项目类型（FH0101）枚举：

| 编码 | 说明 | 编码 | 说明 |
| ---- | ---- | ---- | ---- |
| FH0101.01.03 | 放射 | FH0101.01.06 | 心电 |
| FH0101.01.03.01 | CT | FH0101.01.07 | 肌电 |
| FH0101.01.03.02 | MRI | FH0101.01.08 | 脑电 |
| FH0101.01.03.03 | 钼钯 | FH0101.01.04 | 超声 |
| FH0101.01.03.04 | DSA | FH0101.01.05 | 内镜 |
| FH0101.01.03.05 | X 射线 | | |
| FH0101.01.03.06 | ECT | | |
| FH0101.01.03.07 | PET-CT | | |
| FH0101.01.03.08 | 骨密度 | | |

返回示例（分页结构，`data.records` 为检查申请与报告记录列表）：

```json
{
    "code": 200,
    "data": {
        "total": 4,
        "current": 1,
        "pages": 1,
        "size": 10,
        "records": [
            {
                "applyId": 91005,
                "applyNo": "X9406954536214723101548",
                "applyStatusCode": 8,
                "applyStatusName": "已取消",
                "applyTime": "2020-01-01 00:00:00",
                "applyDeptId": 91095576,
                "applyDeptName": "示例科室区域",
                "applyDoctorJobNumber": "xxxx",
                "applyDoctorName": "示例人员",
                "executeDeptId": 96295357,
                "executeDeptName": "示例科",
                "examinationUserName": "xxx",
                "examinationTime": "2020-01-01 00:00:00",
                "subTypeCode": "BZJC-2022",
                "subTypeName": "示例检查项目",
                "itemId": 9629,
                "itemCode": "JC-2022-4588",
                "itemName": "示例检查项目",
                "imagingFindings": "示例检查所见",
                "imagingDiagnosis": "示例检查",
                "reportUserJobNumber": "xxxx",
                "reportUserName": "示例人",
                "reportCheckUserJobNumber": "xxxx",
                "reportCheckUserName": "示例人",
                "reportTime": "2020-01-01 00:00:00",
                "reportCheckTime": "2020-01-01 00:00:00",
                "reportExtension": "reportExtension",
                "imageId": "pacs_image_id",
                "healthcareType": "FH0108.02",
                "healthcareRecordId": 96074,
                "healthcareRecordNo": "967174494086175886654557",
                "medicalRecordNo": "924627387",
                "patientId": 962315,
                "patientName": "示例人员",
                "campusId": 98143320,
                "campusName": "示例医院第一院区"
            }
        ]
    },
    "success": true
}
```

分页参数与 `data` 结构说明：

| 参数 | 类型 | 说明 |
| ---- | ---- | ---- |
| total | Long | 总记录数 |
| current | Long | 当前页码 |
| pages | Long | 总页数 |
| size | Long | 每页数量 |
| records | List | 检查申请与报告记录列表 |

`records` 元素（检查申请与报告信息）字段如下：

| 参数 | 类型 | 是否必须 | 说明 |
| ---- | ---- | ---- | ---- |
| applyId | Long | 是 | 申请单 ID |
| applyNo | String | 是 | 申请单号（业务主键，如 X94069...） |
| applyStatus / applyStatusCode | String / Integer | 是 | 申请状态编码（如 8-已报告/已完成，2 的幂次位设计） |
| applyStatusName | String | 是 | 申请状态名称（如 已取消/已执行/已报告） |
| applyTime | String | 是 | 申请时间，格式：yyyy-MM-dd HH:mm:ss |
| applyDeptId | Long | 是 | 申请科室 ID |
| applyDeptName | String | 是 | 申请科室名称 |
| applyDoctorJobNumber | String | 是 | 申请医生工号 |
| applyDoctorName | String | 是 | 申请医生姓名 |
| examinationNo | String | 否 | 检查号 |
| examinationUserJobNumber | String | 否 | 检查医师工号 |
| executeDeptId | Long | 是 | 执行科室 ID |
| executeDeptName | String | 是 | 执行科室名称 |
| examinationUserName | String | 是 | 检查医师姓名 |
| examinationTime | String | 是 | 检查执行时间，格式：yyyy-MM-dd HH:mm:ss |
| ItemType | String | 是 | 检查项目类型（FH0101 编码，与入参 itemTypes 同源） |
| ItemCode | String | 是 | 检查项目编码 |
| ItemName | String | 是 | 检查项目名称 |
| partId / partCode / partName | Long / String / String | 否 | 检查部位 ID / 部位编码 / 部位名称 |
| subTypeCode | String | 是 | 检查子类型编码（如 BZJC-2022） |
| subTypeName | String | 是 | 检查子类型/项目名称 |
| itemId | Long | 是 | 检查项目 ID |
| itemCode | String | 是 | 检查项目编码（如 JC-2022-4588） |
| itemName | String | 是 | 检查项目名称 |
| imagingFindings | String | 是 | 影像所见（影像学表现） |
| imagingDiagnosis | String | 是 | 影像诊断结论 |
| reportUserJobNumber | String | 是 | 报告人工号 |
| reportUserName | String | 是 | 报告人姓名 |
| reportCheckUserJobNumber | String | 是 | 报告审核人工号 |
| reportCheckUserName | String | 是 | 报告审核人姓名 |
| reportTime | String | 是 | 报告时间，格式：yyyy-MM-dd HH:mm:ss |
| reportCheckTime | String | 是 | 报告审核时间，格式：yyyy-MM-dd HH:mm:ss |
| reportImageUrl | String | 是 | 报告影像访问地址 |
| reportPdfUrl | String | 是 | 报告 PDF 下载访问地址 |
| reportExtension | String | 否 | 报告扩展信息（其他结构化指标数据） |
| imageId | String | 否 | PACS 影像 ID |
| healthcareType | String | 是 | 诊疗类型编码（FH0108，如 FH0108.02-住院） |
| healthcareRecordId | Long | 是 | 诊疗记录 ID |
| healthcareRecordNo | String | 是 | 诊疗记录号 |
| medicalRecordNo | String | 是 | 病历号 |
| patientId | Long | 是 | 患者 ID |
| patientName | String | 是 | 患者姓名 |
| campusId | Long | 是 | 院区 ID |
| campusName | String | 是 | 院区名称 |

> 说明：该接口为分页查询（current + size）。`data` 为标准分页结构 `total/current/pages/size/records`；`applyStatusCode` 采用 2 的幂次位设计（0/1-待申请、2-已预约、4-已到检、8-已报告、16-已取消等），具体取值请以平台值域文档为准。

