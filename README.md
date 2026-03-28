# Technical Documentation

## Deployment of UniEvent on AWS (3-Tier Architecture)

**Lead Architect:** Muhammad Mehdi Raza (Registration: 2023466)  
**Institution:** GIKI, Software Engineering  
**Repository:** Assignment1_AWS_CE

### 1. Project Mission and Objectives

UniEvent was designed as a cloud-native university event platform with the following architectural goals:

1. **High Availability:** Continuous service during peak demand periods such as society recruitment drives and festivals.
2. **Automated Data Ingestion:** Zero-manual-entry integration with an external Open API for official event retrieval.
3. **Security and Isolation:** Complete isolation of application servers from direct public internet access.
4. **Persistent Storage:** Durable, decoupled storage for event posters and JSON records in Amazon S3.

### 2. Network Architecture (VPC Design)

A custom VPC was designed to enforce isolation and fault tolerance.

1. **Multi-AZ Subnet Strategy:** Four subnets distributed across `us-east-1a` and `us-east-1c`.
2. **Public Tier:** Application Load Balancer (ALB) deployed in public subnets with Internet Gateway access.
3. **Private Application Tier:** EC2 instances deployed without public IP addresses.
4. **Outbound Connectivity:** NAT Gateway configured in the public tier to allow secure outbound API calls from private instances.

### 3. Security and Identity Framework

Security was implemented through IAM and layered Security Groups.

1. **IAM Role-Based Access:** `UniEvent-S3-Role` grants temporary permissions to S3 and AWS Systems Manager, removing the need for static credentials.
2. **Load Balancer Security Group (`UniEvent-LB-SG`):** Allows inbound HTTP traffic on port 80 from `0.0.0.0/0`.
3. **Application Security Group (`UniEvent-Web-SG`):** Allows inbound traffic only from `UniEvent-LB-SG`, preventing direct access to EC2 instances.

### 4. Deployment Challenges and Resolutions

#### 4.1 SSM Connectivity in Private Subnets

**Issue:** Instances were not reachable through AWS Systems Manager; status remained offline.

**Root Causes:**

- Missing managed SSM policy on the instance role.
- No outbound internet route from private subnets.

**Resolution:**

1. Attached `AmazonSSMManagedInstanceCore` to the IAM role.
2. Configured NAT Gateway routing and updated private route tables (`0.0.0.0/0 -> NAT`).

#### 4.2 502 Bad Gateway Through ALB

**Issue:** ALB was active, but requests returned 502 errors.

**Root Cause:** EC2 targets were not configured as web servers.

**Resolution:** Implemented `setup_webserver.sh` to automate Apache and PHP installation and initialize application nodes.

### 5. Application Logic and API Automation

The `events.php` service drives runtime integration and data persistence.

1. **API Source:** Ticketmaster Discovery API selected for structured event metadata (title, venue, date, poster URL).
2. **Automated Persistence Workflow:**
   - Fetches API JSON via server-side `curl`.
   - Writes timestamped JSON records to S3 (for example: `university_events_2026-03-28_01-36-54.json`).
3. **Media Rendering:** Poster URLs are rendered as University Event Posters in the UI.
4. **Storage Target:** `unievent-media-assignment1-2026` S3 bucket.

### 6. Verification and Results

1. **Load Balancing and Availability:** `output/Picture1.png` confirms ALB DNS routing to healthy private-tier instances.
2. **S3 Synchronization:** `output/Picture2.png` validates successful storage of event data to S3.
3. **End-to-End Integration:** `output/Picture3.png` demonstrates live display of multiple events fetched from the API.

### 7. Repository Structure

- `scripts/events.php`: API ingestion, event rendering, and S3 synchronization logic.
- `scripts/setup_webserver.sh`: Apache/PHP bootstrap and web-server provisioning.
- `scripts/university_events_2026-03-28_01-36-54.json`: Persisted event data snapshot.
- `output/Picture1.png`, `output/Picture2.png`, `output/Picture3.png`: Deployment evidence.

### Conclusion

The UniEvent deployment satisfies core AWS 3-tier architecture requirements: high availability, secure network isolation, role-based access control, automated Open API integration, and persistent decoupled cloud storage.
