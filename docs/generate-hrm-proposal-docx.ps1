$ErrorActionPreference = 'Stop'

$outputPath = Join-Path $PSScriptRoot '..\HRM_System_Module_Proposal_SRS_Royal_Aesthetics_Clinic.docx'
$outputPath = [System.IO.Path]::GetFullPath($outputPath)

$word = $null
$doc = $null

function Add-Paragraph {
    param(
        [Parameter(Mandatory = $true)][object]$Document,
        [Parameter(Mandatory = $true)][string]$Text,
        [string]$Style = 'Normal',
        [int]$FontSize = 11,
        [bool]$Bold = $false,
        [int]$Alignment = 0
    )

    $paragraph = $Document.Content.Paragraphs.Add()
    $paragraph.Range.Text = $Text
    $paragraph.Range.Style = $Style
    $paragraph.Range.Font.Size = $FontSize
    $paragraph.Range.Font.Bold = [int]$Bold
    $paragraph.Alignment = $Alignment
    $paragraph.SpaceAfter = 6
    $paragraph.Range.InsertParagraphAfter() | Out-Null

    return $paragraph
}

function Add-BulletList {
    param(
        [Parameter(Mandatory = $true)][object]$Document,
        [Parameter(Mandatory = $true)][string[]]$Items
    )

    foreach ($item in $Items) {
        $paragraph = $Document.Content.Paragraphs.Add()
        $paragraph.Range.Text = $item
        $paragraph.Range.Style = 'Normal'
        $paragraph.Range.Font.Size = 11
        $paragraph.Range.ListFormat.ApplyBulletDefault() | Out-Null
        $paragraph.SpaceAfter = 0
        $paragraph.Range.InsertParagraphAfter() | Out-Null
    }

    $Document.Content.Paragraphs.Add().Range.InsertParagraphAfter() | Out-Null
}

function Add-BudgetTable {
    param(
        [Parameter(Mandatory = $true)][object]$Document
    )

    $range = $Document.Content
    $range.Collapse(0)
    $table = $Document.Tables.Add($range, 8, 2)
    $table.Style = 'Table Grid'
    $table.Range.Font.Size = 10
    $table.Cell(1, 1).Range.Text = 'Item'
    $table.Cell(1, 2).Range.Text = 'Estimated Cost (PKR)'

    $rows = @(
        @('Requirement analysis and module planning', '20,000'),
        @('Employee record management', '30,000'),
        @('Attendance module with ZKTeco integration', '45,000'),
        @('Leave management', '22,000'),
        @('Salary / payroll management', '48,000'),
        @('Reports, testing, deployment and training', '20,000'),
        @('Total Estimated Development Cost', '185,000')
    )

    for ($i = 0; $i -lt $rows.Count; $i++) {
        $table.Cell($i + 2, 1).Range.Text = $rows[$i][0]
        $table.Cell($i + 2, 2).Range.Text = $rows[$i][1]
    }

    for ($col = 1; $col -le 2; $col++) {
        $table.Cell(1, $col).Range.Bold = 1
        $table.Cell(1, $col).Shading.BackgroundPatternColor = 15987699
    }

    $table.Cell(8, 1).Range.Bold = 1
    $table.Cell(8, 2).Range.Bold = 1
    $table.Rows.Alignment = 1
    $table.Columns.Item(1).Width = 360
    $table.Columns.Item(2).Width = 140

    $Document.Content.InsertParagraphAfter() | Out-Null
    $Document.Content.Paragraphs.Last.Range.InsertParagraphAfter() | Out-Null
}

try {
    $word = New-Object -ComObject Word.Application
    $word.Visible = $false
    $doc = $word.Documents.Add()

    Add-Paragraph -Document $doc -Text 'HRM System Module Proposal & SRS' -Style 'Title' -FontSize 24 -Bold $true -Alignment 1 | Out-Null
    Add-Paragraph -Document $doc -Text 'Royal Aesthetics Clinic' -Style 'Subtitle' -FontSize 14 -Alignment 1 | Out-Null
    Add-Paragraph -Document $doc -Text 'Prepared on: 21 May 2026' -Style 'Normal' -FontSize 11 -Alignment 1 | Out-Null
    Add-Paragraph -Document $doc -Text 'Project context: Extension module for the existing CRM / clinic management system' -Style 'Normal' -FontSize 11 -Alignment 1 | Out-Null

    Add-Paragraph -Document $doc -Text '1. Executive Summary' -Style 'Heading 1' -FontSize 16 -Bold $true | Out-Null
    Add-Paragraph -Document $doc -Text 'This document presents the proposal and Software Requirements Specification for a new Human Resource Management (HRM) module to be added to the current project. The objective of this module is to centralize employee information, automate attendance using the existing ZKTeco attendance machine, streamline leave approval, and manage salary processing in one secure system.' | Out-Null
    Add-Paragraph -Document $doc -Text 'The proposed HRM module will reduce manual record keeping, improve HR visibility, and support management with accurate attendance, leave, and payroll reporting.' | Out-Null

    Add-Paragraph -Document $doc -Text '2. Proposal Overview' -Style 'Heading 1' -FontSize 16 -Bold $true | Out-Null
    Add-BulletList -Document $doc -Items @(
        'Proposed Module Name: Human Resource Management (HRM) Module',
        'Objective: Employee records, attendance through ZKTeco, leave management, salary / payroll management, and other core HRM operations',
        'Recommended Delivery Timeline: 6 weeks',
        'Free Maintenance: 2 months after deployment and go-live',
        'Proposed Development Budget: PKR 185,000'
    )

    Add-Paragraph -Document $doc -Text '3. Business Goals' -Style 'Heading 1' -FontSize 16 -Bold $true | Out-Null
    Add-BulletList -Document $doc -Items @(
        'Maintain a complete employee database in one system',
        'Eliminate manual attendance calculations',
        'Integrate biometric attendance records from ZKTeco devices',
        'Improve leave approval workflow and leave balance tracking',
        'Automate salary preparation based on attendance, leave, and payroll settings',
        'Provide management reports for HR operations'
    )

    Add-Paragraph -Document $doc -Text '4. Module Scope' -Style 'Heading 1' -FontSize 16 -Bold $true | Out-Null

    Add-Paragraph -Document $doc -Text '4.1 Employee Records' -Style 'Heading 2' -FontSize 13 -Bold $true | Out-Null
    Add-BulletList -Document $doc -Items @(
        'Employee profile creation and update',
        'Employee code / ID management',
        'Personal, contact and emergency details',
        'CNIC / national ID details',
        'Department, designation, branch and reporting manager',
        'Joining date, employment type, status and probation status',
        'Document storage reference for HR files',
        'Search, filter and export employee listing'
    )

    Add-Paragraph -Document $doc -Text '4.2 Attendance Management with ZKTeco' -Style 'Heading 2' -FontSize 13 -Bold $true | Out-Null
    Add-BulletList -Document $doc -Items @(
        'Integration with ZKTeco attendance machine',
        'Employee-device user mapping',
        'Attendance log synchronization from machine to system',
        'In time / out time calculation',
        'Shift assignment support',
        'Grace time, late arrival, early exit and absent rules',
        'Daily attendance register and monthly summary',
        'Manual attendance correction by authorized users with audit trail'
    )

    Add-Paragraph -Document $doc -Text '4.3 Leave Management' -Style 'Heading 2' -FontSize 13 -Bold $true | Out-Null
    Add-BulletList -Document $doc -Items @(
        'Leave type setup such as casual, sick, annual and unpaid leave',
        'Leave quota / balance setup',
        'Employee leave application',
        'Approval / rejection workflow',
        'Half-day and full-day leave support',
        'Leave calendar and leave reports'
    )

    Add-Paragraph -Document $doc -Text '4.4 Salary / Payroll Management' -Style 'Heading 2' -FontSize 13 -Bold $true | Out-Null
    Add-BulletList -Document $doc -Items @(
        'Salary structure setup for each employee',
        'Basic salary, allowances and deductions',
        'Attendance-linked payroll calculation',
        'Late, absent and unpaid leave deduction rules',
        'Overtime, bonus and incentive entry',
        'Advance / loan deduction support if required',
        'Monthly payroll generation and payslip generation',
        'Salary payment status tracking and payroll reports'
    )

    Add-Paragraph -Document $doc -Text '4.5 Other Core HRM Features' -Style 'Heading 2' -FontSize 13 -Bold $true | Out-Null
    Add-BulletList -Document $doc -Items @(
        'Department management',
        'Designation management',
        'Shift and schedule management',
        'Holiday calendar management',
        'Role-based access for HR, Admin and Management',
        'HR dashboard and summary reports',
        'Basic employee activity / audit logs',
        'Announcement / HR notice section if required'
    )

    Add-Paragraph -Document $doc -Text '5. Functional Requirements' -Style 'Heading 1' -FontSize 16 -Bold $true | Out-Null
    Add-Paragraph -Document $doc -Text '5.1 User Roles' -Style 'Heading 2' -FontSize 13 -Bold $true | Out-Null
    Add-BulletList -Document $doc -Items @(
        'Super Admin',
        'HR Manager',
        'HR Staff',
        'Accounts / Payroll User',
        'Reporting Manager',
        'Read-only Management User'
    )

    Add-Paragraph -Document $doc -Text '5.2 Employee Record Requirements' -Style 'Heading 2' -FontSize 13 -Bold $true | Out-Null
    Add-BulletList -Document $doc -Items @(
        'Add, edit, deactivate and view employee records',
        'Filter employee data by department, designation, status and joining date',
        'Maintain one unique employee ID per employee',
        'Store salary setup reference for payroll generation',
        'Retain historical records for inactive employees'
    )

    Add-Paragraph -Document $doc -Text '5.3 Attendance Requirements' -Style 'Heading 2' -FontSize 13 -Bold $true | Out-Null
    Add-BulletList -Document $doc -Items @(
        'Receive attendance records from ZKTeco machine',
        'Match punches with mapped employees',
        'Generate daily attendance based on assigned shift rules',
        'Mark present, absent, late, early out, holiday, weekend and leave status',
        'Allow authorized correction with reason and audit record',
        'Provide attendance reports by employee and date range'
    )

    Add-Paragraph -Document $doc -Text '5.4 Leave Requirements' -Style 'Heading 2' -FontSize 13 -Bold $true | Out-Null
    Add-BulletList -Document $doc -Items @(
        'Configure leave types and yearly quotas',
        'Allow leave application entry',
        'Allow approval or rejection by authorized roles',
        'Update leave balances automatically after approval',
        'Prevent overuse of leave where policy does not allow it'
    )

    Add-Paragraph -Document $doc -Text '5.5 Payroll Requirements' -Style 'Heading 2' -FontSize 13 -Bold $true | Out-Null
    Add-BulletList -Document $doc -Items @(
        'Maintain salary structure per employee',
        'Calculate payroll based on salary setup and attendance data',
        'Apply configured deductions and allowances',
        'Generate monthly payroll sheet and payslips',
        'Track salary payment status'
    )

    Add-Paragraph -Document $doc -Text '5.6 Reports' -Style 'Heading 2' -FontSize 13 -Bold $true | Out-Null
    Add-BulletList -Document $doc -Items @(
        'Employee list',
        'Attendance daily report',
        'Attendance monthly summary',
        'Leave balance report',
        'Leave application report',
        'Payroll summary',
        'Salary slip / payslip',
        'Department-wise HR summary'
    )

    Add-Paragraph -Document $doc -Text '6. ZKTeco Integration Requirements' -Style 'Heading 1' -FontSize 16 -Bold $true | Out-Null
    Add-Paragraph -Document $doc -Text 'Attendance should be captured from the existing ZKTeco attendance machine and used in attendance and salary calculations.' | Out-Null
    Add-BulletList -Document $doc -Items @(
        'ZKTeco device users will be mapped to employees in the HRM module',
        'Attendance logs will be fetched from the device or through a supported local sync method',
        'The system will store raw logs and process them into attendance records',
        'Processed attendance will be used in attendance reports and payroll calculations',
        'The client will provide device access, credentials and network availability where required'
    )

    Add-Paragraph -Document $doc -Text '7. Non-Functional Requirements' -Style 'Heading 1' -FontSize 16 -Bold $true | Out-Null
    Add-BulletList -Document $doc -Items @(
        'Web-based module integrated into the existing system',
        'Responsive interface for desktop and tablet use',
        'Role-based security and protected access',
        'Audit logging for critical HR changes',
        'Reliable data backup through the existing project/server strategy',
        'Pakistan timezone support for attendance and payroll periods'
    )

    Add-Paragraph -Document $doc -Text '8. Deliverables' -Style 'Heading 1' -FontSize 16 -Bold $true | Out-Null
    Add-BulletList -Document $doc -Items @(
        'HRM module planning and requirement finalization',
        'Employee record management module',
        'Attendance module with ZKTeco integration',
        'Leave management module',
        'Salary / payroll management module',
        'Reports and dashboards',
        'User role / permission setup',
        'Testing and deployment support',
        '2 months free maintenance after deployment'
    )

    Add-Paragraph -Document $doc -Text '9. Timeline' -Style 'Heading 1' -FontSize 16 -Bold $true | Out-Null
    Add-BulletList -Document $doc -Items @(
        'Week 1: Final requirement discussion, database planning and module architecture',
        'Week 2: Employee records and HR master data',
        'Week 3: ZKTeco attendance integration and attendance processing',
        'Week 4: Leave management, approvals and reports',
        'Week 5: Salary structure, payroll processing and payslips',
        'Week 6: Testing, fixes, training, deployment and go-live support'
    )

    Add-Paragraph -Document $doc -Text '10. Budget Estimate' -Style 'Heading 1' -FontSize 16 -Bold $true | Out-Null
    Add-BudgetTable -Document $doc
    Add-BulletList -Document $doc -Items @(
        'Budget includes module development, integration into the existing project, ZKTeco attendance integration effort, testing, deployment support and 2 months free maintenance.',
        'Budget excludes ZKTeco hardware, third-party paid middleware if separately required, server upgrade costs, legal / tax consultancy and major features outside the agreed scope.'
    )

    Add-Paragraph -Document $doc -Text '11. Maintenance and Support' -Style 'Heading 1' -FontSize 16 -Bold $true | Out-Null
    Add-BulletList -Document $doc -Items @(
        '2 months free maintenance after go-live',
        'Includes bug fixing, small configuration updates, minor report or form adjustments and basic operational support',
        'Does not include new major features, new third-party integrations beyond scope, or hardware troubleshooting unrelated to software'
    )

    Add-Paragraph -Document $doc -Text '12. Payment Terms' -Style 'Heading 1' -FontSize 16 -Bold $true | Out-Null
    Add-BulletList -Document $doc -Items @(
        '50% advance at project approval',
        '30% after core module completion and UAT review',
        '20% at final deployment / handover'
    )

    Add-Paragraph -Document $doc -Text '13. Assumptions and Dependencies' -Style 'Heading 1' -FontSize 16 -Bold $true | Out-Null
    Add-BulletList -Document $doc -Items @(
        'This HRM module will be added to the current project architecture',
        'Existing project authentication and user management will be reused where practical',
        'Final salary rules, deduction policy and leave policy will be confirmed by the client before payroll implementation is finalized',
        'If ZKTeco device communication requires a specific connector or utility, the client will provide access to it'
    )

    Add-Paragraph -Document $doc -Text '14. Conclusion' -Style 'Heading 1' -FontSize 16 -Bold $true | Out-Null
    Add-Paragraph -Document $doc -Text 'The proposed HRM module will provide a structured and scalable HR solution inside the current system. It will help Royal Aesthetics Clinic manage employees, attendance, leave and payroll from one platform while reducing manual work and improving operational accuracy.' | Out-Null
    Add-BulletList -Document $doc -Items @(
        'Module: HRM System',
        'Delivery timeline: 6 weeks',
        'Free maintenance: 2 months',
        'Estimated budget: PKR 185,000'
    )

    $formatDocx = 16
    $doc.SaveAs([ref]$outputPath, [ref]$formatDocx)
    $doc.Close()
    $word.Quit()

    Write-Output "DOCX_CREATED: $outputPath"
}
catch {
    if ($doc -ne $null) {
        try { $doc.Close() } catch {}
    }
    if ($word -ne $null) {
        try { $word.Quit() } catch {}
    }

    throw
}
