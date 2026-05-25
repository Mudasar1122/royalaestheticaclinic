# HRM System Module Proposal & SRS

Prepared for: Royal Aesthetics Clinic
Prepared on: 21 May 2026
Project context: Extension module for the existing CRM / clinic management system
Document type: Proposal + Software Requirements Specification (SRS)

## 1. Executive Summary

This document presents the proposal and Software Requirements Specification for a new Human Resource Management (HRM) module to be added to the current project. The objective of this module is to centralize employee information, automate attendance using the existing ZKTeco attendance machine, streamline leave approval, and manage salary processing in one secure system.

The proposed HRM module will reduce manual record keeping, improve HR visibility, and support management with accurate attendance, leave, and payroll reporting.

## 2. Proposal Overview

### Proposed Module Name
Human Resource Management (HRM) Module

### Objective
To build a complete HRM module inside the existing project for managing:

- Employee records
- Attendance through ZKTeco attendance machine
- Leave management
- Salary / payroll management
- Other core HRM operations and reports

### Recommended Delivery Timeline
Estimated development timeline: 6 weeks

### Free Maintenance
2 months free maintenance after deployment and go-live

### Proposed Development Budget
Total estimated cost: PKR 185,000

## 3. Business Goals

The HRM module is intended to achieve the following business goals:

- Maintain a complete employee database in one system
- Eliminate manual attendance calculations
- Integrate biometric attendance records from ZKTeco devices
- Improve leave approval workflow and leave balance tracking
- Automate salary preparation based on attendance, leave, and payroll settings
- Provide management reports for HR operations

## 4. Module Scope

The following items are included in scope for this HRM module:

### 4.1 Employee Records

- Employee profile creation and update
- Employee code / ID management
- Personal details
- Contact details
- CNIC / national ID details
- Department, designation, branch, reporting manager
- Date of joining, employment type, status, probation status
- Emergency contact details
- Salary profile reference
- Document storage reference for appointment letter, CNIC copy, certificates and other HR files
- Search, filter and export employee listing

### 4.2 Attendance Management with ZKTeco

- Integration with ZKTeco attendance machine
- Employee-device user mapping
- Attendance log synchronization from machine to system
- In time / out time calculation
- Shift assignment support
- Grace time, late arrival, early exit and absent rules
- Daily attendance register
- Monthly attendance summary
- Manual attendance correction by authorized users
- Attendance audit trail for corrections and overrides

### 4.3 Leave Management

- Leave type setup such as casual leave, sick leave, annual leave and unpaid leave
- Leave quota / balance setup
- Employee leave application
- Approval / rejection workflow
- Leave balance deduction after approval
- Leave calendar view
- Half-day and full-day leave support
- Leave remarks and approval notes
- Leave reports by employee, department and date range

### 4.4 Salary / Payroll Management

- Salary structure setup for each employee
- Basic salary definition
- Allowances management
- Deductions management
- Attendance-linked payroll calculation
- Late deduction, absent deduction and unpaid leave deduction rules
- Overtime, bonus and incentive entry
- Advance / loan deduction support if required
- Monthly payroll generation
- Payslip generation
- Salary payment status tracking
- Payroll reports

### 4.5 Other Core HRM Features

- Department management
- Designation management
- Shift and schedule management
- Holiday calendar management
- Role-based access for HR, Admin and Management
- HR dashboard for employee count, attendance summary, leave summary and payroll summary
- Basic employee activity / audit logs
- Announcement / HR notice section if required within current project design

## 5. Functional Requirements

### 5.1 User Roles

The system should support role-based access such as:

- Super Admin
- HR Manager
- HR Staff
- Accounts / Payroll User
- Reporting Manager
- Read-only Management User

Each role should only access the screens and actions assigned to it.

### 5.2 Employee Record Requirements

The system shall:

- Allow HR/Admin to add, edit, deactivate and view employee records
- Allow employee data to be filtered by department, designation, status and joining date
- Maintain one unique employee ID per employee
- Store salary setup reference for payroll generation
- Retain historical records for inactive employees

### 5.3 Attendance Requirements

The system shall:

- Receive attendance records from ZKTeco machine
- Match machine punches with mapped employees
- Generate daily attendance based on assigned shift rules
- Mark present, absent, late, early out, holiday, weekend and leave status
- Allow authorized correction with reason and audit record
- Provide attendance reports by employee and date range

### 5.4 Leave Requirements

The system shall:

- Allow HR to configure leave types and yearly quotas
- Allow leave application entry
- Allow approval or rejection by authorized roles
- Update leave balances automatically after approval
- Prevent overuse of leave where policy does not allow it

### 5.5 Payroll Requirements

The system shall:

- Maintain salary structure per employee
- Calculate payroll based on salary setup and attendance data
- Apply configured deductions and allowances
- Generate monthly payroll sheet
- Generate payslips
- Track salary payment status

### 5.6 Reports

The system shall provide reports for:

- Employee list
- Attendance daily report
- Attendance monthly summary
- Leave balance report
- Leave application report
- Payroll summary
- Salary slip / payslip
- Department-wise HR summary

## 6. ZKTeco Integration Requirements

### Integration Objective
Attendance should be captured from the existing ZKTeco attendance machine and used in attendance and salary calculations.

### Expected Integration Flow

- ZKTeco device users will be mapped to employees in the HRM module
- Attendance logs will be fetched from the device or through supported local sync method
- The system will store raw logs and process them into attendance records
- Processed attendance will be used in attendance reports and payroll calculations

### Integration Assumptions

- The client will provide access to the ZKTeco device and network where required
- The client will provide device user list / machine credentials / API or software access if needed
- Hardware cost is not included in this proposal

## 7. Non-Functional Requirements

The HRM module should meet the following non-functional requirements:

- Web-based module integrated into the existing system
- Responsive interface for desktop and tablet use
- Role-based security and protected access
- Audit logging for critical HR changes
- Reliable data backup through existing project/server strategy
- Acceptable performance for routine HR operations
- Pakistan timezone support for attendance and payroll periods

## 8. Deliverables

The following deliverables are included:

- HRM module planning and requirement finalization
- Employee record management module
- Attendance module with ZKTeco integration
- Leave management module
- Salary / payroll management module
- Reports and dashboards
- User role / permission setup
- Testing and deployment support
- 2 months free maintenance after deployment

## 9. Timeline

### Proposed Delivery Plan

Week 1

- Final requirement discussion
- Database planning
- Screen flow and module architecture

Week 2

- Employee records
- Departments, designations and HR master data

Week 3

- ZKTeco attendance integration
- Shift, attendance rules and attendance processing

Week 4

- Leave management
- Leave approvals and reports

Week 5

- Salary structure and payroll processing
- Payslip generation

Week 6

- Testing
- Bug fixes
- User training
- Deployment and go-live support

## 10. Budget Estimate

### Budget Breakdown

| Item | Estimated Cost (PKR) |
| --- | ---: |
| Requirement analysis and module planning | 20,000 |
| Employee record management | 30,000 |
| Attendance module with ZKTeco integration | 45,000 |
| Leave management | 22,000 |
| Salary / payroll management | 48,000 |
| Reports, testing, deployment and training | 20,000 |
| **Total Estimated Development Cost** | **185,000** |

### Budget Includes

- Development of the HRM module
- Integration into the existing project
- ZKTeco attendance integration effort
- Testing and deployment support
- 2 months free maintenance

### Budget Excludes

- ZKTeco hardware or replacement hardware
- Third-party paid software or paid middleware if separately required by the device
- Hosting/server upgrade cost if the current server requires scaling
- Legal / tax consultancy for payroll policy definition
- Major feature additions outside the scope listed above

## 11. Maintenance and Support

2 months free maintenance will be provided after go-live.

This maintenance includes:

- Bug fixing
- Small configuration updates
- Minor report or form adjustments
- Basic operational support for the delivered HRM module

This maintenance does not include:

- New major features
- New third-party integrations beyond the agreed scope
- Hardware troubleshooting unrelated to software implementation

## 12. Payment Terms

Recommended payment terms:

- 50% advance at project approval
- 30% after core module completion and UAT review
- 20% at final deployment / handover

## 13. Assumptions and Dependencies

- This HRM module will be added to the current project architecture
- Existing project authentication and user management will be reused where practical
- Final salary rules, deduction policy and leave policy will be confirmed by the client before payroll implementation is finalized
- If ZKTeco device communication requires a specific connector or utility, the client will provide access to it

## 14. Conclusion

The proposed HRM module will provide a structured and scalable HR solution inside the current system. It will help Royal Aesthetics Clinic manage employees, attendance, leave and payroll from one platform while reducing manual work and improving operational accuracy.

Proposal Summary:

- Module: HRM System
- Delivery timeline: 6 weeks
- Free maintenance: 2 months
- Estimated budget: PKR 185,000

