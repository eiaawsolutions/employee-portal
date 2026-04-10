# Cloud Provider Service Mapping

Quick-reference for mapping abstract infrastructure concepts to concrete services across AWS, Azure, and GCP.

## Compute

| Concept | AWS | Azure | GCP |
|---|---|---|---|
| Virtual Machines | EC2 | Virtual Machines | Compute Engine |
| Containers (managed) | ECS / Fargate | Container Apps / ACI | Cloud Run / GKE Autopilot |
| Kubernetes (managed) | EKS | AKS | GKE |
| Serverless functions | Lambda | Functions | Cloud Functions |
| PaaS (app hosting) | Elastic Beanstalk / App Runner | App Service | App Engine |
| Batch processing | AWS Batch | Batch | Cloud Batch / Dataflow |

## Networking

| Concept | AWS | Azure | GCP |
|---|---|---|---|
| Virtual network | VPC | VNet | VPC |
| Load balancer (L7) | ALB | Application Gateway | Cloud Load Balancing (HTTP) |
| Load balancer (L4) | NLB | Load Balancer | Cloud Load Balancing (TCP/UDP) |
| CDN | CloudFront | Front Door / CDN | Cloud CDN |
| DNS | Route 53 | DNS Zone | Cloud DNS |
| API Gateway | API Gateway / AppSync | API Management | API Gateway / Apigee |
| Service mesh | App Mesh | — (use Istio on AKS) | Traffic Director / Istio on GKE |
| VPN | Site-to-Site VPN | VPN Gateway | Cloud VPN |
| Private link | PrivateLink | Private Link | Private Service Connect |
| WAF | WAF | WAF (on Front Door / App GW) | Cloud Armor |
| DDoS protection | Shield | DDoS Protection | Cloud Armor |

## Databases

| Concept | AWS | Azure | GCP |
|---|---|---|---|
| Managed PostgreSQL | RDS PostgreSQL / Aurora PostgreSQL | Database for PostgreSQL (Flexible) | Cloud SQL / AlloyDB |
| Managed MySQL | RDS MySQL / Aurora MySQL | Database for MySQL (Flexible) | Cloud SQL for MySQL |
| Managed SQL Server | RDS SQL Server | SQL Database / SQL Managed Instance | Cloud SQL for SQL Server |
| NoSQL (document) | DynamoDB | Cosmos DB | Firestore / Bigtable |
| NoSQL (key-value) | DynamoDB / ElastiCache | Cosmos DB (Table API) / Cache for Redis | Memorystore / Firestore |
| Redis (managed) | ElastiCache for Redis / MemoryDB | Cache for Redis | Memorystore for Redis |
| Graph database | Neptune | Cosmos DB (Gremlin API) | — (use Neo4j on GCE) |
| Time-series | Timestream | — (use TimescaleDB on VM) | — (use TimescaleDB on Cloud SQL) |
| Search | OpenSearch Service | AI Search | — (use Elasticsearch on GCE) |

## Storage

| Concept | AWS | Azure | GCP |
|---|---|---|---|
| Object storage | S3 | Blob Storage | Cloud Storage |
| Block storage | EBS | Managed Disks | Persistent Disk |
| File storage (NFS) | EFS | Files | Filestore |
| Archive storage | S3 Glacier | Blob (Archive tier) | Cloud Storage (Archive) |

## Messaging & Events

| Concept | AWS | Azure | GCP |
|---|---|---|---|
| Message queue | SQS | Service Bus Queue / Storage Queue | Cloud Tasks / Pub/Sub |
| Pub/sub | SNS / EventBridge | Event Grid / Service Bus Topics | Pub/Sub |
| Streaming | Kinesis | Event Hubs | Pub/Sub / Dataflow |
| Workflow orchestration | Step Functions | Durable Functions / Logic Apps | Workflows |

## Identity & Security

| Concept | AWS | Azure | GCP |
|---|---|---|---|
| IAM | IAM | Entra ID (Azure AD) + RBAC | IAM |
| Secrets management | Secrets Manager / Parameter Store | Key Vault | Secret Manager |
| Certificate management | ACM | Key Vault / App Service Certificates | Certificate Manager |
| Key management (KMS) | KMS | Key Vault (Keys) | Cloud KMS |

## Observability

| Concept | AWS | Azure | GCP |
|---|---|---|---|
| Logging | CloudWatch Logs | Monitor Logs (Log Analytics) | Cloud Logging |
| Metrics / monitoring | CloudWatch Metrics | Monitor Metrics | Cloud Monitoring |
| Distributed tracing | X-Ray | Application Insights | Cloud Trace |
| APM | X-Ray + CloudWatch | Application Insights | Cloud Trace + Cloud Profiler |
| Alerting | CloudWatch Alarms / SNS | Monitor Alerts | Cloud Monitoring Alerting |

## CI/CD

| Concept | AWS | Azure | GCP |
|---|---|---|---|
| Source control | CodeCommit (deprecated → GitHub) | Azure Repos / GitHub | Cloud Source Repos / GitHub |
| CI/CD pipeline | CodePipeline + CodeBuild | Azure Pipelines / GitHub Actions | Cloud Build |
| Artifact registry | ECR (containers) / CodeArtifact | ACR (containers) / Artifacts | Artifact Registry |
| IaC | CloudFormation / CDK | Bicep / ARM / Terraform | Deployment Manager / Terraform |

## Cost Optimization Tips

| Provider | Key Strategy |
|---|---|
| **AWS** | Use Savings Plans + Spot Instances for compute. S3 Intelligent-Tiering for storage. Right-size with Compute Optimizer. |
| **Azure** | Use Reserved Instances + Spot VMs. Azure Advisor for recommendations. Hybrid Benefit for Windows/SQL licenses. |
| **GCP** | Committed Use Discounts + Preemptible VMs. Recommender for right-sizing. Sustained use discounts (automatic). |

## Provider Selection Decision Tree

```
Is the team already invested in a cloud provider?
├── Yes → Use that provider (migration cost > optimization gains)
└── No  → What's the primary workload?
    ├── Enterprise / .NET / Windows → Azure
    ├── Broadest service catalog / startup ecosystem → AWS
    ├── Data/ML-heavy / Kubernetes-native → GCP
    └── Multi-cloud requirement → Use Terraform + provider-agnostic patterns
```
