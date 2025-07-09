# Simplex Internal Portal - Complete Workflow Diagram

## System Overview Workflow

```mermaid
graph TD
    A[User Access] --> B{Authentication}
    B -->|Valid Credentials| C[LDAP Verification]
    B -->|Invalid| D[Login Error]
    D --> B
    
    C -->|Success| E[Role & Department Check]
    C -->|Failure| D
    
    E -->|ADMIN Role| F[Admin Dashboard]
    E -->|USER Role + HRD Dept| G[HRD User Portal]
    E -->|USER Role + Purchase Dept| H[Purchase User Portal]
    E -->|USER Role + IT Dept| I[IT User Portal]
    E -->|USER Role + Other Dept| J[Normal User Portal]
    
    F --> K[Admin Modules]
    G --> L[HRD Modules]
    H --> M[Purchase Modules]
    I --> N[IT Modules]
    J --> O[General Modules]
```

## Authentication Flow

```mermaid
sequenceDiagram
    participant U as User
    participant L as Login Page
    participant H as Login Handler
    participant P as Python LDAP
    participant LDAP as Active Directory
    participant DB as Database
    participant D as Dashboard

    U->>L: Enter Credentials
    L->>H: POST Credentials
    H->>P: Call LDAP Script
    P->>LDAP: NTLM Authentication
    LDAP-->>P: Employee ID
    P-->>H: SUCCESS:EMPCODE
    H->>DB: Fetch User Role & Department
    DB-->>H: Role & Dept Data
    H->>H: Determine Redirect Path
    H->>D: Redirect to Appropriate Dashboard
```

## Admin Dashboard Workflow

```mermaid
graph TD
    A[Admin Login] --> B[Admin Dashboard]
    B --> C[SAP & Reports]
    B --> D[HR Corner]
    B --> E[Employee Corner]
    B --> F[Project Management]
    
    C --> C1[SAP API Tester]
    C --> C2[SAP Production]
    C --> C3[SAP Quality]
    C --> C4[SAP Development]
    C --> C5[Item Group Details]
    C --> C6[Part Description Bifurcation]
    C --> C7[PO Issues]
    
    D --> D1[HR Dashboard Analytics]
    D --> D2[New Employee Registration]
    D --> D3[Employee Master]
    D --> D4[Profile Update Requests]
    
    E --> E1[LMS Dashboard]
    
    F --> F1[Project Creation]
    F --> F2[Task Management]
    F --> F3[Gantt Charts]
    F --> F4[Kanban Board]
```

## Employee Registration Workflow

```mermaid
graph TD
    A[Start Registration] --> B[Page 1: Personal Details]
    B --> B1[Name, DOB, Address]
    B --> B2[Identification Documents]
    B --> B3[Physical Details]
    
    B --> C[Page 2: Family & Contact]
    C --> C1[Marital Status]
    C --> C2[Emergency Contacts]
    C --> C3[Email & Phone]
    
    C --> D[Page 3: Work Experience]
    D --> D1[Past Experience]
    D --> D2[PF Account Details]
    D --> D3[Extra Curricular]
    
    D --> E[Page 4: Other Details]
    E --> E1[Medical Information]
    E --> E2[Previous Employment]
    E --> E3[Declaration]
    
    E --> F[File Uploads]
    F --> F1[Profile Picture]
    F --> F2[Signature]
    F --> F3[Documents]
    
    F --> G[Database Save]
    G --> H[HR Details Entry]
    H --> I[IT Details Entry]
    I --> J[Registration Complete]
```

## HR Analytics Workflow

```mermaid
graph TD
    A[HR Dashboard Access] --> B[Data Queries]
    B --> C[Active Employee Count]
    B --> D[Department Breakdown]
    B --> E[Unit Breakdown]
    B --> F[Designation Breakdown]
    B --> G[Department Head Analysis]
    B --> H[Hiring Trends]
    B --> I[Gender Distribution]
    B --> J[Attendance Policy]
    
    C --> K[Charts & Visualizations]
    D --> K
    E --> K
    F --> K
    G --> K
    H --> K
    I --> K
    J --> K
    
    K --> L[Interactive Dashboard]
    L --> M[Filter Options]
    L --> N[Export Data]
    L --> O[Real-time Updates]
```

## PO Issues Management Workflow

```mermaid
graph TD
    A[PO Issues Dashboard] --> B[Issue Creation]
    A --> C[View All Issues]
    A --> D[Track My Issues]
    A --> E[Closed Issues]
    
    B --> B1[Fill Issue Details]
    B1 --> B2[Upload Documents]
    B2 --> B3[Submit Issue]
    B3 --> B4[Issue Logged]
    
    C --> C1[Filter Issues]
    C1 --> C2[Status Updates]
    C2 --> C3[Add Remarks]
    C3 --> C4[Export to Excel]
    
    D --> D1[My Open Issues]
    D --> D2[My Closed Issues]
    D --> D3[Issue History]
    
    E --> E1[Resolution Tracking]
    E1 --> E2[Performance Analytics]
```

## SAP Integration Workflow

```mermaid
graph TD
    A[SAP API Tester] --> B[Connect to SAP]
    B --> C[Discover Entity Sets]
    C --> D[Select Entity]
    D --> E[Extract Attributes]
    E --> F[Build Query]
    F --> G[Execute Query]
    G --> H[Display Results]
    
    H --> I[Export Data]
    H --> J[Save Query]
    H --> K[Share Results]
```

## Project Management Workflow

```mermaid
graph TD
    A[Project Dashboard] --> B[Create Project]
    A --> C[View Projects]
    A --> D[Project Analytics]
    
    B --> B1[Project Details]
    B1 --> B2[Assign Owner]
    B2 --> B3[Set Timeline]
    B3 --> B4[Project Created]
    
    C --> C1[Project List]
    C1 --> C2[Task Management]
    C2 --> C3[Team Assignment]
    
    C2 --> D1[Task List View]
    C2 --> D2[Kanban Board]
    C2 --> D3[Gantt Chart]
    
    D --> E[Progress Tracking]
    E --> F[Performance Metrics]
```

## Learning Management Workflow

```mermaid
graph TD
    A[LMS Dashboard] --> B[Course Catalog]
    A --> C[My Learning]
    A --> D[Progress Tracking]
    A --> E[Certifications]
    
    B --> B1[Browse Courses]
    B1 --> B2[Enroll in Course]
    B2 --> B3[Start Learning]
    
    C --> C1[Active Courses]
    C1 --> C2[Completed Courses]
    C2 --> C3[Certificates]
    
    D --> D1[Learning Analytics]
    D1 --> D2[Skill Assessment]
    D2 --> D3[Performance Reports]
```

## Data Flow Architecture

```mermaid
graph TD
    A[User Interface Layer] --> B[Application Layer]
    B --> C[Business Logic Layer]
    C --> D[Data Access Layer]
    D --> E[Database Layer]
    
    E --> E1[user_master_db]
    E --> E2[simplexinternal]
    E --> E3[project_management_db]
    
    B --> F[External APIs]
    F --> F1[SAP S4HANA]
    F --> F2[LDAP/Active Directory]
    F --> F3[Google Gemini AI]
    
    C --> G[File Storage]
    G --> G1[Profile Pictures]
    G --> G2[Documents]
    G --> G3[Signatures]
```

## Security Workflow

```mermaid
graph TD
    A[User Request] --> B[Session Check]
    B -->|Valid Session| C[Role Verification]
    B -->|Invalid Session| D[Redirect to Login]
    
    C -->|Authorized| E[Access Granted]
    C -->|Unauthorized| F[Access Denied]
    
    E --> G[Request Processing]
    G --> H[Input Validation]
    H --> I[SQL Injection Check]
    I --> J[XSS Prevention]
    J --> K[File Upload Validation]
    K --> L[Process Request]
    
    D --> M[LDAP Authentication]
    M --> N[Database Role Check]
    N --> O[Session Creation]
    O --> P[Redirect to Dashboard]
```

## Error Handling Workflow

```mermaid
graph TD
    A[System Error] --> B{Error Type}
    B -->|Authentication| C[Login Error]
    B -->|Database| D[Database Error]
    B -->|File Upload| E[Upload Error]
    B -->|SAP Connection| F[SAP Error]
    B -->|Validation| G[Validation Error]
    
    C --> C1[Display Login Message]
    C1 --> C2[Log Error]
    C2 --> C3[Redirect to Login]
    
    D --> D1[Database Error Handler]
    D1 --> D2[Log Error]
    D2 --> D3[User Notification]
    
    E --> E1[File Validation Error]
    E1 --> E2[User Feedback]
    E2 --> E3[Retry Upload]
    
    F --> F1[SAP Connection Error]
    F1 --> F2[Fallback Options]
    F2 --> F3[Manual Process]
    
    G --> G1[Form Validation Error]
    G1 --> G2[Highlight Fields]
    G2 --> G3[User Correction]
```

## Database Relationships

```mermaid
erDiagram
    users ||--o{ user_hr_details : has
    users ||--o{ user_it_details : has
    users ||--o{ parent_details : has
    users ||--o{ issue_logs : creates
    
    users {
        int user_id PK
        string first_name
        string surname
        string profile_picture_path
        string your_email_id
        string your_phone_number
    }
    
    user_hr_details {
        int user_id FK
        string employee_id_ascent
        string department
        string designation
        string employee_role
        string employee_portal_status
        date date_of_joining
    }
    
    user_it_details {
        int user_id FK
        string official_email
        string official_phone_number
        string intercom_number
    }
    
    parent_details {
        int user_id FK
        string parent_type
        string name
        string contact_number
    }
    
    issue_logs {
        int issue_id
        int action_by_userid FK
        string action_type
        string remarks
    }
```

## System Integration Points

```mermaid
graph TD
    A[Simplex Internal Portal] --> B[LDAP Authentication]
    A --> C[SAP S4HANA Integration]
    A --> D[File Storage System]
    A --> E[Email System]
    A --> F[Reporting System]
    
    B --> B1[Active Directory]
    B1 --> B2[Employee ID Sync]
    
    C --> C1[Production Environment]
    C --> C2[Quality Environment]
    C --> C3[Development Environment]
    
    D --> D1[Profile Pictures]
    D --> D2[Document Storage]
    D --> D3[Signature Files]
    
    E --> E1[Notification Emails]
    E --> E2[Status Updates]
    
    F --> F1[Excel Export]
    F --> F2[PDF Reports]
    F --> F3[Analytics Dashboard]
```

## User Journey Map

```mermaid
journey
    title Simplex Internal Portal User Journey
    section Employee Onboarding
      Login: 5: Employee
      Complete Registration: 4: Employee, HR
      HR Details Entry: 3: HR
      IT Setup: 3: IT
      Access Granted: 5: Employee
    section Daily Operations
      Dashboard Access: 5: All Users
      Module Navigation: 4: All Users
      Data Entry: 3: All Users
      Report Generation: 4: Managers
      Issue Resolution: 3: All Users
    section Learning & Development
      LMS Access: 4: Employees
      Course Enrollment: 3: Employees
      Progress Tracking: 4: Employees
      Certification: 5: Employees
    section Project Management
      Project Creation: 4: Project Managers
      Task Assignment: 3: Project Managers
      Progress Monitoring: 4: All Users
      Project Completion: 5: Project Managers
```

This comprehensive workflow diagram shows the complete system architecture, user flows, data relationships, and integration points of the Simplex Internal Portal. The system provides a robust, secure, and user-friendly platform for enterprise management with role-based access control and comprehensive business process automation. 