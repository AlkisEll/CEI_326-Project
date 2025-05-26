<?php
session_start();
require_once 'database.php';
require_once 'get_config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$latest_app_query = mysqli_query($conn, "SELECT * FROM applications WHERE user_id = $user_id ORDER BY id DESC LIMIT 1");
$previous_application = mysqli_fetch_assoc($latest_app_query);

$education_entries = [];
$experience_entries = [];
$course_ids = [];
$uploaded_files = [];

if ($previous_application) {
    $previous_app_id = $previous_application['id'];

    // Restore Step 2: Get previously selected course IDs
    $course_ids_result = mysqli_query($conn, "SELECT course_id FROM application_courses WHERE application_id = $previous_app_id");
    while ($row = mysqli_fetch_assoc($course_ids_result)) {
        $course_ids[] = $row['course_id'];
    }
    // Restore Step 3
    $education_entries[] = [
        'level' => $previous_application['degree_level'],
        'title' => $previous_application['degree_title'],
        'institution' => $previous_application['institution'],
        'from' => $previous_application['education_start_date'],
        'to' => $previous_application['education_end_date'],
        'country' => $previous_application['institution_country'],
        'grade' => $previous_application['degree_grade'],
        'thesis' => $previous_application['thesis_title'],
        'qualifications' => $previous_application['additional_qualifications']
    ];

    // Restore Step 4
    $experience_entries[] = [
        'job_title' => $previous_application['current_position'],
        'employer' => $previous_application['current_employer'],
        'summary' => $previous_application['professional_experience'],
        'from' => $previous_application['experience_start_date'],
        'to' => $previous_application['experience_end_date'],    
        'type' => $previous_application['part_or_full_time'] ?? '',
        'expertise' => $previous_application['expertise_area'],
        'projects' => $previous_application['project_highlights']
    ];
}

$form_submitted_success = isset($_GET['form_submitted']) && $_GET['form_submitted'] == 1;


$user_query = mysqli_query($conn, "SELECT full_name, phone, email, dob, address, country, postcode FROM users WHERE id = $user_id");
$user_data = mysqli_fetch_assoc($user_query);

$full_name_parts = preg_split('/\s+/', $user_data['full_name']);
$first_name = $full_name_parts[0];
$last_name = end($full_name_parts);
$middle_name = count($full_name_parts) > 2 ? implode(' ', array_slice($full_name_parts, 1, -1)) : null;

$phone = $user_data['phone'];
$email = $user_data['email'];
$date_of_birth = $user_data['dob'];
$address = $user_data['address'];
$country = $user_data['country'];
$postal_code = $user_data['postcode'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Application Form - CUT</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="indexstyle.css">
  <link rel="stylesheet" href="darkmode.css">
</head>
<body>

<?php include "navbar.php"; ?>

<div class="container py-5">
  <?php if ($previous_application): ?>
<div class="modal fade" id="restoreModal" tabindex="-1" aria-labelledby="restoreModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="restoreModalLabel">Restore Previous Application?</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p>You have submitted a previous application. Do you wish to restore your previous details to pre-fill this form?</p>
      </div>
      <div class="modal-footer">
  <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No, Start Fresh</button>
  <button type="button" class="btn btn-primary" id="restoreBtn">Yes, Restore Data</button>
  <button type="button" id="forceCloseModal" class="btn btn-secondary d-none" data-bs-dismiss="modal">Close Hidden</button>
</div>
    </div>
  </div>
</div>
<?php endif; ?>
  <div class="my-apps-card">
    <h2 class="mb-4 text-center"><i class="bi bi-journal-text me-2"></i>Special Scientist Application Form</h2>
     <button type="button" class="btn btn-outline-danger btn-sm" id="clearPrefillBtn" style="display: none;">
    <i class="bi bi-x-circle me-1"></i> Clear Prefilled Data
  </button>

    <?php if ($form_submitted_success): ?>
      <div class="alert alert-success">
        <h4>🎉 Application Submitted!</h4>
        <p>Your form has been submitted successfully.</p>
        <a href="my_applications.php" class="btn btn-primary rounded-pill">View My Applications</a>
      </div>
    <?php endif; ?>

    <!-- Step Overview Boxes -->
<div class="step-scroll-wrapper">
  <div class="step-guide mb-4" id="stepGuide">
    <!-- Boxes will be generated dynamically -->
  </div>
</div>

<?php if (!$form_submitted_success): ?>

<!-- Modal for Education Entry -->
<div class="modal fade" id="educationModal" tabindex="-1" aria-labelledby="educationModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form id="educationForm">
        <div class="modal-header">
          <h5 class="modal-title" id="educationModalLabel">Add Education Entry</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body row g-3">
          <div class="col-md-6">
            <label class="form-label">Degree Level *</label>
            <select class="form-select" name="degree_level" id="degree_level" required onchange="toggleDegreeFields()">
            <option value="">-- Select --</option>
            <option>Bachelor (BSc, BA)</option>
            <option>Master (MSc, MA)</option>
            <option>PhD / Doctorate</option>
            <option>Postdoctoral</option>
            <option value="none">No Degree yet</option>
            </select>
          </div>
          <div class="col-md-6" id="degree_title_group">
            <label class="form-label">Degree Title *</label>
            <input type="text" class="form-control" name="degree_title" required>
          </div>
          <div class="col-md-6" id="institution_group">
            <label class="form-label">Institution *</label>
            <input type="text" class="form-control" name="institution" required>
          </div>
          <div class="col-md-6" id="from_to_group">
  <div class="row">
    <div class="col-md-6">
      <label class="form-label">From *</label>
      <input type="date" class="form-control" name="start_date" id="start_date" required>
    </div>
    <div class="col-md-6">
      <label class="form-label">To *</label>
      <input type="date" class="form-control" name="end_date" id="end_date" required>
    </div>
  </div>
</div>

<div class="col-md-6" id="due_date_group" style="display: none;">
  <label class="form-label">Expected Graduation Date *</label>
  <input type="date" class="form-control" name="due_date" id="due_date">
</div>
          <div class="col-md-6" id="institution_country_group">
            <label class="form-label">Institution Country *</label>
            <select class="form-select" name="institution_country" required>
            <option value="">-- Select Country --</option>
            <option>Cyprus</option>
            <option>Greece</option>
            <option>UK</option>
            <option>Germany</option>
            <option>France</option>
            <option>Italy</option>
            <option>Spain</option>
            <option>USA</option>
            <option>Canada</option>
            <option>Australia</option>
            <option>Netherlands</option>
            <option>Sweden</option>
            <option>Norway</option>
            <option>Finland</option>
            <option>Belgium</option>
            <option>Switzerland</option>
            <option>Austria</option>
            <option>Other</option>
          </select>
          </div>
          <div class="col-md-6" id="degree_grade_group">
            <label class="form-label">Degree Grade / Classification (Optional)</label>
            <input type="text" class="form-control" name="degree_grade">
          </div>
          <div class="col-md-6">
            <label class="form-label">Thesis Title / Research Topic (Optional)</label>
            <input type="text" class="form-control" name="thesis_title">
          </div>
          <div class="col-md-6">
            <label class="form-label">Additional Qualifications (Optional)</label>
            <input type="text" class="form-control" name="additional_qualifications">
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">Save Entry</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</div>
<!-- Modal for Professional Experience Entry -->
<div class="modal fade" id="experienceModal" tabindex="-1" aria-labelledby="experienceModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form id="experienceForm">
        <div class="modal-header">
          <h5 class="modal-title" id="experienceModalLabel">Add Your Professional Experience Entry</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body row g-3">
          <div class="col-md-6">
            <label class="form-label">Job Title *</label>
            <input type="text" class="form-control" name="job_title" placeholder='If none, type "none" and save your entry' required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Job Position *</label>
            <input type="text" class="form-control" name="employer" required>
          </div>
          <div class="col-12">
            <label class="form-label">Experience Summary *</label>
            <textarea name="experience_summary" class="form-control" rows="4" placeholder="Describe your experience..." required></textarea>
          </div>
          <div class="col-md-6">
            <label class="form-label">From *</label>
            <input type="date" class="form-control" name="experience_from" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">To *</label>
            <input type="date" class="form-control" name="experience_to" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Part or Full Time? *</label>
            <select class="form-select" name="job_type" required>
              <option value="">-- Select --</option>
              <option>Part Time</option>
              <option>Full Time</option>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">Expertise Area *</label>
            <select class="form-select" name="expertise_area" required>
              <option value="">-- Select --</option>
              <option>Research</option>
              <option>Teaching</option>
              <option>Academic Administration</option>
              <option>Industry/Field Work</option>
            </select>
          </div>
          <div class="col-12">
            <label class="form-label">Projects/Publications (Optional)</label>
            <textarea name="project_highlights" class="form-control" rows="3"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">Save Entry</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</div>
<form id="applicationForm" action="save_application_form.php" method="POST" enctype="multipart/form-data">
<?php endif; ?>
      <div class="step" id="step-1">
        <h4>Step 1 of 7: Personal Information</h4>
        <div class="step-indicator position-relative mb-3">
        <div class="step-indicator-fill step-progress"></div>
        <div class="progress-text progress-percentage">14%</div>
</div>

        <div class="form-group mb-2">
          <label>First Name *</label>
          <input type="text" class="form-control" value="<?= htmlspecialchars($first_name) ?>" readonly>
        </div>
        <?php if ($middle_name): ?>
          <div class="form-group mb-2">
            <label>Middle Name</label>
            <input type="text" class="form-control" value="<?= htmlspecialchars($middle_name) ?>" readonly>
          </div>
        <?php endif; ?>
        <div class="form-group mb-2">
          <label>Last Name *</label>
          <input type="text" class="form-control" value="<?= htmlspecialchars($last_name) ?>" readonly>
        </div>
        <div class="form-group mb-2">
          <label>ID Card / Passport Number *</label>
          <input type="text" name="id_card" class="form-control" required>
        </div>
        <div class="form-group mb-2">
          <label>Social Security Number (Optional)</label>
          <input type="text" name="social_security" class="form-control">
        </div>
        <div class="mb-3">
    <label for="nationality" class="form-label">Nationality *</label>
    <select class="form-select" id="nationality" name="nationality" onchange="toggleOtherNationality()">
        <option value="">Select your nationality</option>
        <option value="Cypriot">Cypriot</option>
        <option value="Greek">Greek</option>
        <option value="British">British</option>
        <option value="German">German</option>
        <option value="French">French</option>
        <option value="Italian">Italian</option>
        <option value="Spanish">Spanish</option>
        <option value="American">American</option>
        <option value="Canadian">Canadian</option>
        <option value="Australian">Australian</option>
        <option value="Dutch">Dutch</option>
        <option value="Swedish">Swedish</option>
        <option value="Norwegian">Norwegian</option>
        <option value="Finnish">Finnish</option>
        <option value="Belgian">Belgian</option>
        <option value="Swiss">Swiss</option>
        <option value="Austrian">Austrian</option>
        <option value="Other">Other</option>
        <!-- You can add more predefined countries here -->
    </select>
</div>

<div class="mb-3" id="otherNationalityContainer" style="display: none;">
    <label for="other_nationality" class="form-label">Please specify your nationality</label>
    <input type="text" class="form-control" name="other_nationality" id="other_nationality">
</div>
        <div class="form-group mb-2">
          <label>Gender *</label>
          <select name="gender" class="form-control" required>
            <option value="">Select Gender</option>
            <option>Male</option>
            <option>Female</option>
            <option>Other</option>
          </select>
        </div>
        <div class="form-group mb-2">
          <label>Date of Birth *</label>
          <input type="date" class="form-control" value="<?= htmlspecialchars($date_of_birth) ?>" readonly>
        </div>
        <div class="form-group mb-2">
          <label>Address *</label>
          <input type="text" class="form-control" value="<?= htmlspecialchars($address) ?>" readonly>
        </div>
        <div class="form-group mb-2">
          <label>Mobile Phone *</label>
          <input type="text" class="form-control" value="<?= htmlspecialchars($phone) ?>" readonly>
        </div>
        <div class="form-group mb-2">
          <label>Country *</label>
          <input type="text" class="form-control" value="<?= htmlspecialchars($country) ?>" readonly>
        </div>
        <div class="form-group mb-2">
          <label>Postal Code *</label>
          <input type="text" class="form-control" value="<?= htmlspecialchars($postal_code) ?>" readonly>
        </div>
        <div class="form-group mb-2">
          <label>House Phone (Optional)</label>
          <input type="text" name="house_phone" class="form-control">
        </div>
        <div class="form-group mb-2">
          <label>University Email (Optional)</label>
          <input type="email" name="university_email" class="form-control">
        </div>
        <div class="form-group mb-3">
          <label>Personal Email *</label>
          <input type="email" class="form-control" value="<?= htmlspecialchars($email) ?>" readonly>
        </div>

        <div class="d-flex justify-content-end">
          <button type="button" class="btn btn-primary rounded-pill px-4" onclick="nextStep()">Next</button>
        </div>
      </div>

            <!-- Step 2: Course & Period Selection -->
            <div class="step" id="step-2" style="display:none;">
            <div class="d-flex justify-content-between align-items-center mb-2">
  <h4 class="mb-0">Step 2 of 7: Course & Application Period</h4>
  <div class="d-flex align-items-center gap-2" style="max-width: 400px;">
  <input type="text" id="courseSearch" class="form-control form-control-sm" placeholder="Search courses...">
  <button type="button" id="clearSearchBtn" class="btn btn-sm btn-outline-secondary" title="Clear search">
    <i class="bi bi-x-circle"></i>
  </button>
</div>
<p id="noResultsMessage" class="text-danger mt-2 ms-1" style="display: none;">
  <i class="bi bi-exclamation-triangle me-1"></i> No matching courses found.
</p>
</div>
        <div class="step-indicator position-relative mb-3">
        <div class="step-indicator-fill step-progress"></div>
        <div class="progress-text progress-percentage">14%</div>
</div>

        <div class="form-group mb-3">
          <label><strong>Select Courses *</strong></label>
          <?php
          $course_data = [];
          $courses = mysqli_query($conn, "
              SELECT c.id, c.course_name, c.course_code, d.name AS department_name
              FROM courses c
              LEFT JOIN departments d ON c.department_id = d.id
              ORDER BY d.name ASC, c.course_name ASC
          ");
          
          while ($row = mysqli_fetch_assoc($courses)) {
              $dept = $row['department_name'] ?: 'Unassigned Department';
              $course_data[$dept][] = $row;
          }
          ?>
          <div class="accordion" id="courseAccordion">
          <?php foreach ($course_data as $department => $courses): ?>
            <div class="accordion-item">
              <h2 class="accordion-header" id="heading<?= md5($department) ?>">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                        data-bs-target="#collapse<?= md5($department) ?>" aria-expanded="false"
                        aria-controls="collapse<?= md5($department) ?>">
                  <?= htmlspecialchars($department) ?>
                </button>
              </h2>
              <div id="collapse<?= md5($department) ?>" class="accordion-collapse collapse"
                   aria-labelledby="heading<?= md5($department) ?>" data-bs-parent="#courseAccordion">
                <div class="accordion-body">
                  <?php foreach ($courses as $course): ?>
                    <div class="form-check mb-1">
                      <input class="form-check-input" type="checkbox" name="course_ids[]" value="<?= $course['id'] ?>" id="course<?= $course['id'] ?>">
                      <label class="form-check-label" for="course<?= $course['id'] ?>">
                        <?= htmlspecialchars($course['course_name']) ?> (<?= htmlspecialchars($course['course_code']) ?>)
                      </label>
                    </div>
                  <?php endforeach; ?>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
          </div>
          <div class="invalid-feedback d-block" id="courseCheckboxError" style="display: none;">
            Please select at least one course.
          </div>
        </div>

        <div class="form-group mb-4">
          <label><strong>Select Application Period *</strong></label>
          <select name="period_id" class="form-select" required>
          oninvalid="this.setCustomValidity('Please select a period.')"
          oninput="this.setCustomValidity('')">
            <option value="">-- Select Period --</option>
            <small class="form-text text-muted">Only currently open application periods are shown.</small>
            <?php
            $periods = mysqli_query($conn, "
  SELECT id, name 
  FROM application_periods 
  WHERE CURDATE() BETWEEN start_date AND end_date
  ORDER BY id DESC
");
            while ($period = mysqli_fetch_assoc($periods)):
            ?>
              <option value="<?= $period['id'] ?>"><?= htmlspecialchars($period['name']) ?></option>
            <?php endwhile; ?>
          </select>
        </div>

        <div class="d-flex justify-content-between">
          <button type="button" class="btn btn-secondary" onclick="prevStep()">Previous</button>
          <button type="button" class="btn btn-primary" onclick="nextStep()">Next</button>
        </div>
      </div>

      <!-- Step 3: Education -->
      <div class="step" id="step-3" style="display:none;">
        <h4>Step 3 of 7: Education</h4>
        <input type="hidden" name="education_json" id="education_json">
        <div class="step-indicator position-relative mb-3">
        <div class="step-indicator-fill step-progress"></div>
        <div class="progress-text progress-percentage">14%</div>
</div>

        <div class="form-group mb-3">
        <div class="mb-3">
  <h5>Educational Qualifications</h5>
  <table class="table table-bordered">
    <thead class="table-light">
      <tr>
        <th>Degree Level</th>
        <th>Degree Title</th>
        <th>Institution</th>
        <th>From</th>
        <th id="toHeader">To</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody id="educationTableBody">
      <tr><td colspan="6" class="text-center text-muted">No entries added yet.</td></tr>
    </tbody>
  </table>
  <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#educationModal">
    <i class="bi bi-plus"></i> Add Education
  </button>
</div>
</div>
        <div class="d-flex justify-content-between">
          <button type="button" class="btn btn-secondary" onclick="prevStep()">Previous</button>
          <button type="button" class="btn btn-primary" onclick="nextStep()">Next</button>
        </div>
      </div>

      <!-- Step 4: Professional Experience -->
<div class="step" id="step-4" style="display:none;">
  <h4>Step 4 of 7: Professional Experience</h4>
  <input type="hidden" name="experience_json" id="experience_json">

  <div class="step-indicator position-relative mb-3">
    <div class="step-indicator-fill step-progress"></div>
    <div class="progress-text progress-percentage">57%</div>
  </div>

  <div class="form-group mb-3">
    <h5>Professional Experience</h5>
    <table class="table table-bordered">
      <thead class="table-light">
  <tr>
    <th>Job Title</th>
    <th>Employer</th>
    <th>From</th>
    <th>To</th>
    <th>Type</th>
    <th>Expertise</th>
    <th>Actions</th>
  </tr>
</thead>
      <tbody id="experienceTableBody">
        <tr><td colspan="6" class="text-center text-muted">No entries added yet.</td></tr>
      </tbody>
    </table>
    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#experienceModal">
      <i class="bi bi-plus"></i> Add Your Professional Experience Entry
    </button>
  </div>

  <div class="d-flex justify-content-between">
    <button type="button" class="btn btn-secondary" onclick="prevStep()">Previous</button>
    <button type="button" class="btn btn-primary" onclick="nextStep()">Next</button>
  </div>
</div>

 <!-- Step 5: Upload Required Documents -->
<div class="step" id="step-5" style="display: none;">
  <h4>Step 5 of 7: Upload Required Documents</h4>
  <div class="step-indicator position-relative mb-3">
    <div class="step-indicator-fill step-progress"></div>
    <div class="progress-text progress-percentage">71%</div>
  </div>

  <!-- Upload CV -->
  <div class="mb-3">
    <label class="form-label">Upload CV *</label>
    <div class="drag-drop-area" data-input="cv_file">
      <p>Drag & drop your CV (PDF) or click to upload</p>
      <input type="file" name="cv_file" class="form-control d-none" accept="application/pdf" required>
      <div class="selected-files" id="cv_file-preview"></div>
      <button type="button" class="btn btn-sm btn-outline-danger mt-2 remove-files-btn" data-input="cv_file" style="display:none;">
        <i class="bi bi-x-circle"></i> Remove File
      </button>
    </div>
  </div>

  <!-- Degree Certificate & Transcript -->
  <div class="mb-3">
    <label class="form-label">Degree Certificate & Transcript *</label>
    <div class="drag-drop-area" data-input="degree_file">
      <p>Drag & drop your files or click to upload (PDF only)</p>
      <input type="file" name="degree_file[]" class="form-control d-none" accept="application/pdf" multiple required>
      <div class="selected-files" id="degree_file-preview"></div>
      <button type="button" class="btn btn-sm btn-outline-danger mt-2 remove-files-btn" data-input="degree_file" style="display:none;">
        <i class="bi bi-x-circle"></i> Remove File
      </button>
    </div>
  </div>

  <!-- Reference Letter (Optional) -->
  <div class="mb-3">
    <label class="form-label">Upload Reference Letter (Optional)</label>
    <div class="drag-drop-area" data-input="reference_letter">
      <p>Drag & drop your reference letter or click to upload (PDF Only)</p>
      <input type="file" name="reference_letter" class="form-control d-none" accept="application/pdf">
      <div class="selected-files" id="reference_letter-preview"></div>
      <button type="button" class="btn btn-sm btn-outline-danger mt-2 remove-files-btn" data-input="reference_letter" style="display:none;">
        <i class="bi bi-x-circle"></i> Remove File
      </button>
    </div>
  </div>

  <!-- Professional Certifications (Optional) -->
  <div class="mb-3">
    <label class="form-label">Professional Certifications (Optional)</label>
    <div class="drag-drop-area" data-input="certifications_file">
      <p>Drag & drop your certifications or click to upload (PDF Only, multiple allowed)</p>
      <input type="file" name="certifications_file[]" class="form-control d-none" accept="application/pdf" multiple>
      <div class="selected-files" id="certifications_file-preview"></div>
      <button type="button" class="btn btn-sm btn-outline-danger mt-2 remove-files-btn" data-input="certifications_file" style="display:none;">
        <i class="bi bi-x-circle"></i> Remove File
      </button>
    </div>
  </div>

  <!-- Other Supporting Documents (Optional) -->
  <div class="mb-3">
    <label class="form-label">Other Supporting Documents (Optional)</label>
    <div class="drag-drop-area" data-input="other_docs">
      <p>Drag & drop additional documents or click to upload (PDF Only, multiple allowed)</p>
      <input type="file" name="other_docs[]" class="form-control d-none" accept="application/pdf" multiple>
      <div class="selected-files" id="other_docs-preview"></div>
      <button type="button" class="btn btn-sm btn-outline-danger mt-2 remove-files-btn" data-input="other_docs" style="display:none;">
        <i class="bi bi-x-circle"></i> Remove File
      </button>
    </div>
  </div>

  <!-- Navigation Buttons -->
  <div class="d-flex justify-content-between">
    <button type="button" class="btn btn-secondary" onclick="prevStep()">Previous</button>
    <button type="button" class="btn btn-primary" onclick="nextStep()">Next</button>
  </div>
</div>

      <!-- Step 6: Responsible Declaration -->
<div class="step" id="step-6" style="display: none;">
  <h4>Step 6 of 7: Responsible Declaration</h4>
  <div class="step-indicator position-relative mb-3">
  <div class="step-indicator-fill step-progress"></div>
  <div class="progress-text progress-percentage">14%</div>
</div>

  <div class="form-group">
  <p>
  I hereby declare that all the information I have provided in this application is true, complete, and correct to the best of my knowledge.
</p>
<p>
  I understand that any misrepresentation, falsification, or omission of information may result in the rejection of my application, cancellation of any offer or appointment, and/or disciplinary action in accordance with the policies of the Cyprus University of Technology (CUT).
</p>
<p>
  I further authorize Staff Members of Cyprus University of Technology to verify the information I have provided and to contact educational institutions, employers, or other sources as necessary to validate credentials and qualifications.
</p>


    <div class="form-check">
      <input class="form-check-input" type="checkbox" name="responsible_declaration" id="responsible_declaration" value="Agreed" required>
      <label class="form-check-label" for="responsible_declaration">
        I agree to the above statements. *
      </label>
    </div>
  </div>

  <div class="d-flex justify-content-between">
    <button type="button" class="btn btn-secondary" onclick="prevStep()">Previous</button>
    <button type="button" class="btn btn-primary" onclick="nextStep()">Next</button>
  </div>
</div>

<!-- Step 7: GDPR Data Protection Consent -->
<div class="step" id="step-7" style="display: none;">
  <h4>Step 7 of 7: GDPR Data Protection Consent</h4>
  <div class="step-indicator position-relative mb-3">
  <div class="step-indicator-fill step-progress"></div>
  <div class="progress-text progress-percentage">14%</div>
</div>

  <div class="form-group">
  <p>
  By submitting this application, I consent to the collection, processing, and storage of my personal data by the Cyprus University of Technology (CUT)
  for the purposes of evaluating my eligibility for academic or research appointments, or for other roles related to special scientific work.
</p>

<p>
  I understand that this data will be retained only for as long as necessary to complete the evaluation process or comply with legal obligations, and will
  be handled securely and confidentially in accordance with the General Data Protection Regulation (GDPR, EU 2016/679).
</p>

<p>
  I acknowledge that I have the right to request access to my data, to rectify or delete it, or to withdraw my consent at any time by contacting the CUT Data Protection Officer.
</p>

<p>
  You may view the full <a href="https://www.cut.ac.cy/privacy" target="_blank">Data Privacy Policy here</a>.
</p>

<p class="text-muted"><em>Note: Refusing to provide consent will prevent submission of your application.</em></p>

    <div class="form-check">
      <input class="form-check-input" type="checkbox" name="gdpr_consent" id="gdpr_consent" value="Agreed" required>
      <label class="form-check-label" for="gdpr_consent">
        I consent to the data processing terms. *
      </label>
    </div>
  </div>

  <div class="d-flex justify-content-between">
    <button type="button" class="btn btn-secondary" onclick="prevStep()">Previous</button>
    <button type="submit" class="btn btn-success">Submit Application</button>
  </div>
</div>
    </form>
</div>

<script>
let currentStep = 1;
function showStep(step) {
  document.querySelectorAll('.step').forEach(el => el.style.display = 'none');
  const current = document.getElementById('step-' + step);
  current.style.display = 'block';

  const percent = Math.round((step / 7) * 100);
  const bar = current.querySelector('.step-indicator-fill');
  const text = current.querySelector('.progress-text');

  if (bar && text) {
    // Animate width
    bar.style.width = percent + '%';

    // Trigger bounce animation
    bar.classList.remove('bounce');
    void bar.offsetWidth; // Force reflow
    bar.classList.add('bounce');

    // Animate number count-up/down
    const currentValue = parseInt(text.textContent) || 0;
    const targetValue = percent;
    const duration = 300; // ms
    const frameRate = 10; // ms
    const steps = duration / frameRate;
    const increment = (targetValue - currentValue) / steps;

    let count = 0;
    const animate = setInterval(() => {
      count++;
      const newValue = Math.round(currentValue + increment * count);
      text.textContent = newValue + '%';
      if (count >= steps) {
        text.textContent = targetValue + '%';
        clearInterval(animate);
      }
    }, frameRate);
  }

  if (step === 5) {
    initializeDragDropHandlers();
  }


  updateStepBoxHighlight(step);
  window.location.hash = 'step' + step;
}

function nextStep() {
  const current = document.getElementById('step-' + currentStep);
  const required = current.querySelectorAll('[required]');
  let valid = true;

  required.forEach(field => {
  // Skip hidden elements (e.g. display: none or detached from layout)
  if (!field.offsetParent) return;

  const feedback = field.parentElement.querySelector('.invalid-feedback');
  if (!field.value || (field.type === 'checkbox' && !field.checked)) {
    field.classList.add('is-invalid');
    if (!feedback) {
      const err = document.createElement('div');
      err.className = 'invalid-feedback';
      err.innerText = 'This field is required';
      field.parentElement.appendChild(err);
    }
    valid = false;
  } else {
    field.classList.remove('is-invalid');
    if (feedback) feedback.remove();
  }
});

  if (currentStep === 2) {
    const checks = document.querySelectorAll('input[name="course_ids[]"]');
    const oneChecked = Array.from(checks).some(c => c.checked);
    const errorBox = document.getElementById('courseCheckboxError');
    if (!oneChecked) {
      errorBox.style.display = 'block';
      valid = false;
    } else {
      errorBox.style.display = 'none';
    }
  }

if (currentStep === 3 && (!window.educationEntries || window.educationEntries.length === 0)) {
  console.log("Next step check - educationEntries:", window.educationEntries);
  alert('Please add at least one educational qualification.');
  return;
}

if (currentStep === 4 && (!window.experienceEntries || window.experienceEntries.length === 0)) {
  console.log("Next step check - experienceEntries:", window.experienceEntries);
  alert('Please add at least one professional experience entry.');
  return;
}

  if (valid) {
    currentStep++;
    showStep(currentStep);
  }
}
function prevStep() {
  currentStep--;
  showStep(currentStep);
}
document.addEventListener('DOMContentLoaded', () => {
  showStep(currentStep);

  const hash = window.location.hash;
if (hash && hash.startsWith('#step')) {
  const stepNum = parseInt(hash.replace('#step', ''), 10);
  if (!isNaN(stepNum) && stepNum >= 1 && stepNum <= 7) {
    currentStep = stepNum;
    showStep(currentStep);
  }
}
  const cypriot = document.getElementById('cypriot');
  const other = document.getElementById('other');
  const inputBox = document.getElementById('otherNationalityInput');
  const input = document.getElementById('nationality');

  function updateNat() {
    if (other.checked) {
      inputBox.style.display = 'block';
      input.required = true;
      input.value = '';
    } else {
      inputBox.style.display = 'none';
      input.required = false;
      input.value = 'Cypriot';
    }
  }
  cypriot.addEventListener('change', updateNat);
  other.addEventListener('change', updateNat);
  updateNat();
});

window.addEventListener('popstate', () => {
  const hash = window.location.hash;
  if (hash && hash.startsWith('#step')) {
    const stepNum = parseInt(hash.replace('#step', ''), 10);
    if (!isNaN(stepNum) && stepNum >= 1 && stepNum <= 7) {
      currentStep = stepNum;
      showStep(currentStep);
    }
  }
});
</script>


<script>
  const darkToggle = document.getElementById('darkModeToggle');
  const darkMobile = document.getElementById('darkModeToggleMobile');
  const body = document.body;

  const savedMode = localStorage.getItem('dark-mode');
  if (savedMode === 'true') {
    body.classList.add('dark-mode');
    if (darkToggle) darkToggle.checked = true;
    if (darkMobile) darkMobile.checked = true;
  }

  function toggleDarkMode(sourceToggle) {
    const isDark = sourceToggle.checked;
    body.classList.toggle('dark-mode', isDark);
    localStorage.setItem('dark-mode', isDark);
    if (darkToggle && sourceToggle !== darkToggle) darkToggle.checked = isDark;
    if (darkMobile && sourceToggle !== darkMobile) darkMobile.checked = isDark;
  }

  if (darkToggle) darkToggle.addEventListener('change', () => toggleDarkMode(darkToggle));
  if (darkMobile) darkMobile.addEventListener('change', () => toggleDarkMode(darkMobile));

document.getElementById('courseSearch').addEventListener('input', function () {
  const query = this.value.trim().toLowerCase();
  const accordionItems = document.querySelectorAll('#courseAccordion .accordion-item');

  accordionItems.forEach(item => {
    let matchFound = false;
    const checks = item.querySelectorAll('.form-check');

    checks.forEach(check => {
      const label = check.querySelector('label');
      const text = label.textContent.toLowerCase();

      // Clear previous states
      label.classList.remove('course-highlight');
      check.style.display = 'none';

      if (query && text.includes(query)) {
        label.classList.add('course-highlight');
        check.style.display = 'block';
        matchFound = true;
      }
    });

    // Collapse or show department based on match
    const collapse = item.querySelector('.accordion-collapse');
    const bsCollapse = bootstrap.Collapse.getOrCreateInstance(collapse);
    if (matchFound) {
      item.style.display = 'block';
      bsCollapse.show();
    } else {
      item.style.display = query ? 'none' : 'block';
      bsCollapse.hide();
    }
  });
});
</script>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const courseSearch = document.getElementById('courseSearch');

  if (!courseSearch) return;

  courseSearch.addEventListener('input', function () {
    const query = this.value.trim().toLowerCase();
    const accordionItems = document.querySelectorAll('#courseAccordion .accordion-item');

    accordionItems.forEach(item => {
      let matchFound = false;
      const checks = item.querySelectorAll('.form-check');

      checks.forEach(check => {
        const label = check.querySelector('label');
        const text = label.textContent.toLowerCase();

        label.classList.remove('course-highlight');
        check.style.display = 'none';

        if (query && text.includes(query)) {
          label.classList.add('course-highlight');
          check.style.display = 'block';
          matchFound = true;
        }

        // Count visible items after filtering
const anyVisible = Array.from(document.querySelectorAll('#courseAccordion .accordion-item'))
  .some(item => item.style.display !== 'none');

document.getElementById('noResultsMessage').style.display = anyVisible ? 'none' : 'block';
      });

        // Handle clear button
  const clearBtn = document.getElementById('clearSearchBtn');
  if (clearBtn) {
    clearBtn.addEventListener('click', function () {
      courseSearch.value = '';
      courseSearch.dispatchEvent(new Event('input'));
    });
  }

      const collapse = item.querySelector('.accordion-collapse');
      const bsCollapse = bootstrap.Collapse.getOrCreateInstance(collapse);
      

      if (matchFound) {
        item.style.display = 'block';
        bsCollapse.show();
      } else {
        item.style.display = query ? 'none' : 'block';
        bsCollapse.hide();
      }
    });
  });
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
const steps = [
  { title: "Personal Info", desc: "Your personal and contact details." },
  { title: "Courses & Period", desc: "Choose courses and application cycle." },
  { title: "Education", desc: "Academic background and degree." },
  { title: "Experience", desc: "Professional history and expertise." },
  { title: "Documents", desc: "Upload CV and certificates." },
  { title: "Declaration", desc: "Confirm information accuracy." },
  { title: "GDPR Consent", desc: "Accept privacy and data policy." }
];

// Render the step boxes and arrows
const stepGuide = document.getElementById('stepGuide');
steps.forEach((step, i) => {
  const box = document.createElement('div');
  box.className = 'step-box' + (i === 0 ? ' active' : '');
  box.id = 'step-box-' + (i + 1);
  box.innerHTML = `
    <div class="step-label">Step ${i + 1}</div>
    <div class="step-title">${step.title}</div>
    <div class="step-desc">${step.desc}</div>
  `;
  stepGuide.appendChild(box);
  if (i < steps.length - 1) {
    const arrow = document.createElement('div');
    arrow.className = 'step-arrow';
    arrow.innerHTML = '→';
    stepGuide.appendChild(arrow);
  }
});

function updateStepBoxHighlight(step) {
  document.querySelectorAll('.step-box').forEach(box => box.classList.remove('active'));
  const activeBox = document.getElementById('step-box-' + step);
  if (activeBox) {
    activeBox.classList.add('active');
    activeBox.scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' });
  }
}
</script>

<script>
let currentEditIndex = null;
window.educationEntries = window.educationEntries || [];
const educationEntries = window.educationEntries;

window.experienceEntries = window.experienceEntries || [];
let experienceEntries = window.experienceEntries;
let currentExperienceIndex = null;

document.addEventListener('DOMContentLoaded', () => {
  const form = document.querySelector('form');
  const educationField = document.getElementById('education_json');

  // Handle modal submission
  document.getElementById('educationForm').addEventListener('submit', function (e) {
    e.preventDefault(); // ✅ This prevents modal auto-dismiss
    const form = e.target;
    
    let toDate = form.end_date.value;
if (form.degree_level.value === 'none') {
  toDate = form.due_date.value;
}

const entry = {
  level: form.degree_level.value,
  title: form.degree_title.value,
  institution: form.institution.value,
  from: form.start_date.value,
  to: toDate,
  country: form.institution_country.value,
  grade: form.degree_grade.value,
  thesis: form.thesis_title.value,
  qualifications: form.additional_qualifications.value
};


    if (typeof currentEditIndex !== 'undefined' && currentEditIndex !== null) {
        educationEntries[currentEditIndex] = entry;
        currentEditIndex = null;
    } else {
        educationEntries.push(entry);
    }

    renderEducationTable(); // or renderEducationEntries()

    const modal = bootstrap.Modal.getInstance(document.getElementById('educationModal'));
    modal.hide(); // ✅ Manually close after handling

    form.reset();
});

  // Handle form submission
  form.addEventListener('submit', function () {
    if (educationField && educationEntries.length > 0) {
      educationField.value = JSON.stringify(educationEntries);
    }
  });
});

// Render table rows
function renderEducationTable() {
  const tbody = document.getElementById('educationTableBody');
  tbody.innerHTML = '';

// Dynamically change header
  const toHeader = document.getElementById('toHeader');
  const hasNoDegree = window.educationEntries.some(entry => entry.level === 'none');
  if (toHeader) {
    toHeader.textContent = hasNoDegree ? 'Expected Graduation Date' : 'To';
  }

  if (window.educationEntries.length === 0) {
    const row = document.createElement('tr');
    row.innerHTML = '<td colspan="6" class="text-center text-muted">No entries added yet.</td>';
    tbody.appendChild(row);
    return;
  }

  window.educationEntries.forEach((edu, i) => {
    const row = document.createElement('tr');
    row.innerHTML = `
      <td>${edu.level}</td>
      <td>${edu.level === 'none' ? 'No Degree Yet' : edu.title}</td>
      <td>${edu.level === 'none' ? '-' : edu.institution}</td>
      <td>${edu.level === 'none' ? '-' : edu.from}</td>
      <td>${edu.level === 'none' ? `<strong title="Expected Graduation Date">${edu.to}</strong>` : edu.to}</td>
      <td>
        <button type="button" class="btn btn-sm btn-outline-primary me-1" onclick="editEducation(${i})">
          <i class="bi bi-pencil"></i>
        </button>
        <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteEducation(${i})">
          <i class="bi bi-trash"></i>
        </button>
      </td>
    `;
    tbody.appendChild(row);
  });
}

// Edit entry
function editEducation(index) {
  const edu = educationEntries[index];
  currentEditIndex = index;

  const form = document.getElementById('educationForm');
  form.degree_level.value = edu.level;
  form.degree_title.value = edu.title;
  form.institution.value = edu.institution;
  form.start_date.value = edu.from;
  form.end_date.value = edu.level === 'none' ? '' : edu.to;
  form.due_date.value = edu.level === 'none' ? edu.to : '';
  form.institution_country.value = edu.country;
  form.degree_grade.value = edu.grade;
  form.thesis_title.value = edu.thesis;
  form.additional_qualifications.value = edu.qualifications;

  const modal = new bootstrap.Modal(document.getElementById('educationModal'));
  modal.show();
}

// Delete entry
function deleteEducation(index) {
  educationEntries.splice(index, 1);
  renderEducationTable();
}
</script>
<script>
document.addEventListener('DOMContentLoaded', function () {
  const form = document.getElementById('applicationForm');
  if (form) {
    form.addEventListener('submit', function () {
      console.log("Submitting form...");
      console.log("Education Entries:", educationEntries);
      const educationField = document.getElementById('education_json');
      if (educationField) {
        educationField.value = JSON.stringify(educationEntries);
      }
    });
  } else {
    console.log("Form with ID 'applicationForm' not found.");
  }
});
</script>
<script>
function toggleOtherNationality() {
    var select = document.getElementById("nationality");
    var otherContainer = document.getElementById("otherNationalityContainer");

    if (select.value === "Other") {
        otherContainer.style.display = "block";
    } else {
        otherContainer.style.display = "none";
        document.getElementById("other_nationality").value = "";
    }
}
</script>
<script>
  document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('educationModal');

    if (!modal) return;

    const fromInput = modal.querySelector('input[name="start_date"]');
    const toInput = modal.querySelector('input[name="end_date"]');

    if (fromInput && toInput) {
      fromInput.addEventListener('change', () => {
        toInput.min = fromInput.value;

        if (toInput.value && toInput.value < fromInput.value) {
          alert('"To" date cannot be earlier than "From" date.');
          toInput.value = '';
        }
      });

      toInput.addEventListener('change', () => {
        if (fromInput.value && toInput.value < fromInput.value) {
          alert('"To" date cannot be earlier than "From" date.');
          toInput.value = '';
        }
      });
    }
  });
</script>
<script>
document.addEventListener('DOMContentLoaded', function () {
  const experienceInput = document.querySelector('input[name="years_experience"]');
  if (experienceInput) {
    experienceInput.addEventListener('keypress', function (e) {
      // Allow only digits (0–9)
      if (!/[0-9]/.test(e.key)) {
        e.preventDefault();
      }
    });

    experienceInput.addEventListener('paste', function (e) {
      // Prevent pasting non-numeric content
      const paste = (e.clipboardData || window.clipboardData).getData('text');
      if (!/^\d+$/.test(paste)) {
        e.preventDefault();
      }
    });
  }
});
</script>
<script>
document.addEventListener('DOMContentLoaded', function () {
  const experienceForm = document.getElementById('experienceForm');
  const jobTitleInput = experienceForm?.querySelector('input[name="job_title"]');

  const matchNone = (value) => {
    const lower = value.trim().toLowerCase();
    return ['none', 'non', 'no'].includes(lower);
  };

  const fieldSelectors = [
    'input[name="employer"]',
    'textarea[name="experience_summary"]',
    'input[name="experience_from"]',
    'input[name="experience_to"]',
    'select[name="job_type"]',
    'select[name="expertise_area"]'
  ];

  function toggleExperienceFields(hide) {
    fieldSelectors.forEach(selector => {
      const field = experienceForm.querySelector(selector);
      if (!field) return;

      // Get the closest Bootstrap column or form-group
      const wrapper = field.closest('.col-md-6, .col-12, .form-group, .mb-3, .row, .col') || field.parentElement;

      if (wrapper) wrapper.style.display = hide ? 'none' : '';
      field.required = !hide;

      if (hide) {
        field.value = selector.includes('select') ? '' : '-';
        field.classList.remove('is-invalid');
      } else {
        if (field.value === '-') field.value = '';
      }
    });
  }

  function handleNoneInput() {
    const value = jobTitleInput?.value || '';
    const isNone = matchNone(value);
    toggleExperienceFields(isNone);
  }

  if (jobTitleInput) {
    jobTitleInput.addEventListener('input', handleNoneInput);
    handleNoneInput(); // Run initially in case the modal is reused
  }
});
</script>

<script>
function toggleDegreeFields() {
  const selected = document.getElementById('degree_level').value;
  const hideAcademicFields = selected === 'none';

  // Hide academic fields
  ['degree_title', 'institution', 'institution_country'].forEach(name => {
  const input = document.querySelector(`[name="${name}"]`);
  const group = input?.closest('.col-md-6') || input?.closest('.col-md-3');
  if (input && group) {
    group.style.display = hideAcademicFields ? 'none' : '';
    input.required = !hideAcademicFields;
    if (hideAcademicFields) input.value = '';
  }
});

// Now separately handle Degree Grade (always optional, but hidden if "No Degree yet")
const gradeInput = document.querySelector('[name="degree_grade"]');
const gradeGroup = gradeInput?.closest('.col-md-6');
if (gradeInput && gradeGroup) {
  gradeGroup.style.display = hideAcademicFields ? 'none' : '';
  if (hideAcademicFields) gradeInput.value = '';
}

// Set "No Degree Yet" as title only once
const degreeTitleInput = document.querySelector('[name="degree_title"]');
if (hideAcademicFields && degreeTitleInput) {
  degreeTitleInput.value = 'No Degree Yet';
}

  // Toggle From/To
  const fromToGroup = document.getElementById('from_to_group');
  const startDate = document.getElementById('start_date');
  const endDate = document.getElementById('end_date');

  if (fromToGroup) {
    fromToGroup.style.display = hideAcademicFields ? 'none' : '';
    if (startDate) {
      startDate.required = !hideAcademicFields;
      if (hideAcademicFields) startDate.value = '';
    }
    if (endDate) {
      endDate.required = !hideAcademicFields;
      if (hideAcademicFields) endDate.value = '';
    }
  }

  // Show Due date
  const dueGroup = document.getElementById('due_date_group');
  const dueDate = document.getElementById('due_date');

  if (dueGroup && dueDate) {
    dueGroup.style.display = hideAcademicFields ? '' : 'none';
    dueDate.required = hideAcademicFields;
    dueDate.disabled = !hideAcademicFields;
    if (!hideAcademicFields) dueDate.value = '';
    
    // Set min to today
    const today = new Date().toISOString().split("T")[0];
    dueDate.setAttribute("min", today);
  }
}

document.addEventListener('DOMContentLoaded', () => {
  const degreeSelect = document.getElementById('degree_level');
  if (degreeSelect) degreeSelect.addEventListener('change', toggleDegreeFields);
});
</script>
<script>
  document.addEventListener('DOMContentLoaded', () => {
  const experienceForm = document.getElementById('experienceForm');
  experienceForm.addEventListener('submit', function (e) {
    e.preventDefault();

    const entry = {
  job_title: this.job_title.value.trim(),
  employer: this.employer.value.trim(),
  summary: this.experience_summary.value.trim(),
  from: this.experience_from.value,
  to: this.experience_to.value,
  type: this.job_type.value,
  expertise: this.expertise_area.value,
  projects: this.project_highlights.value.trim()
};

    if (currentExperienceIndex !== null) {
      experienceEntries[currentExperienceIndex] = entry;
      currentExperienceIndex = null;
    } else {
      experienceEntries.push(entry);
    }

    renderExperienceTable();
    bootstrap.Modal.getInstance(document.getElementById('experienceModal')).hide();
    this.reset();
  });

  document.getElementById('applicationForm').addEventListener('submit', function () {
  const field = document.getElementById('experience_json');
  if (field && window.experienceEntries) {
    field.value = JSON.stringify(window.experienceEntries);
  }
});
});

function renderExperienceTable() {
  const tbody = document.getElementById('experienceTableBody');
  tbody.innerHTML = '';

  const entries = window.experienceEntries || [];

  if (entries.length === 0) {
    const row = document.createElement('tr');
    row.innerHTML = '<td colspan="7" class="text-center text-muted">No entries added yet.</td>';
    tbody.appendChild(row);
    return;
  }

  entries.forEach((exp, i) => {
    const isNone = ['none', 'non', 'no'].includes(exp.job_title?.trim().toLowerCase());
    const row = document.createElement('tr');

    row.innerHTML = `
      <td>${exp.job_title || '-'}</td>
      <td>${isNone ? '-' : exp.employer || '-'}</td>
      <td>${isNone ? '-' : exp.from || '-'}</td>
      <td>${isNone ? '-' : exp.to || '-'}</td>
      <td>${isNone ? '-' : exp.type || '-'}</td>
      <td>${isNone ? '-' : exp.expertise || '-'}</td>
      <td>
        <button class="btn btn-sm btn-outline-primary me-1" onclick="editExperience(${i})"><i class="bi bi-pencil"></i></button>
        <button class="btn btn-sm btn-outline-danger" onclick="deleteExperience(${i})"><i class="bi bi-trash"></i></button>
      </td>
    `;

    tbody.appendChild(row);
  });
}

function editExperience(index) {
  const exp = experienceEntries[index];
  currentExperienceIndex = index;

  const form = document.getElementById('experienceForm');
  form.job_title.value = exp.job_title;
  form.employer.value = exp.employer;
  form.experience_summary.value = exp.summary;
  form.experience_from.value = exp.from;
  form.experience_to.value = exp.to;
  form.job_type.value = exp.type;

  bootstrap.Modal.getInstance(document.getElementById('experienceModal')).show();
}

function deleteExperience(index) {
  experienceEntries.splice(index, 1);
  renderExperienceTable();
}
</script>
<script>
window.previousApplication = <?= json_encode($previous_application ?? null) ?>;
window.previousCourseIds = <?= json_encode($course_ids ?? []) ?>;
window.educationFromPHP = <?= json_encode($education_entries ?? []) ?>;
window.experienceFromPHP = <?= json_encode($experience_entries ?? []) ?>;
</script>
<script>
document.addEventListener('DOMContentLoaded', () => {
  const fromInput = document.querySelector('input[name="experience_from"]');
  const toInput = document.querySelector('input[name="experience_to"]');

  if (!fromInput || !toInput) return;

  fromInput.addEventListener('change', () => {
    // Set "To" date cannot be before the "From" date
    toInput.min = fromInput.value;

    // Reset if invalid
    if (toInput.value && toInput.value < fromInput.value) {
      toInput.value = '';
      alert('"To" date cannot be earlier than "From" date.');
    }
  });

  toInput.addEventListener('change', () => {
    if (fromInput.value && toInput.value < fromInput.value) {
      toInput.value = '';
      alert('"To" date cannot be earlier than "From" date.');
    }
  });

  // Set default min date as today for “To” field (if "From" is not yet selected)
  const today = new Date().toISOString().split('T')[0];
  toInput.setAttribute('min', today);
});
</script>
<script>
function initializeDragDropHandlers() {
  const dragAreas = document.querySelectorAll('.drag-drop-area');

  dragAreas.forEach(area => {
    const inputName = area.getAttribute('data-input');
    const input = area.querySelector('input[type="file"]');
    const preview = document.getElementById(`${inputName}-preview`);
    const removeBtn = area.querySelector('.remove-files-btn');

    if (!input || !preview || !removeBtn) return;

    let lastClickTime = 0;
area.addEventListener('click', (e) => {
  const now = Date.now();
  if (now - lastClickTime < 300) return; // prevent fast double click
  lastClickTime = now;

  if (!e.target.closest('input') && !e.target.closest('button')) {
    input.click();
  }
});

    ['dragenter', 'dragover'].forEach(event => {
      area.addEventListener(event, e => {
        e.preventDefault();
        area.classList.add('dragover');
      });
    });

    ['dragleave', 'drop'].forEach(event => {
      area.addEventListener(event, e => {
        e.preventDefault();
        area.classList.remove('dragover');
      });
    });

    area.addEventListener('drop', e => {
      const dt = new DataTransfer();
      Array.from(e.dataTransfer.files).forEach(file => dt.items.add(file));
      input.files = dt.files;
      displayFileNames(input.files, preview, removeBtn);
    });

    input.addEventListener('change', () => {
      displayFileNames(input.files, preview, removeBtn);
    });

    removeBtn.addEventListener('click', () => {
      input.value = ''; // Clear the file input
      preview.innerHTML = '<small class="text-muted">No file selected</small>';
      removeBtn.style.display = 'none';
    });

    function displayFileNames(files, previewElement, removeBtn) {
      previewElement.innerHTML = '';
      if (!files || files.length === 0) {
        previewElement.innerHTML = '<small class="text-muted">No file selected</small>';
        removeBtn.style.display = 'none';
        return;
      }

      Array.from(files).forEach(file => {
        const div = document.createElement('div');
        div.innerHTML = `<i class="bi bi-file-earmark-pdf me-1 text-danger"></i> ${file.name}`;
        previewElement.appendChild(div);
      });

      removeBtn.style.display = 'inline-block';
    }
  });
}

document.addEventListener('DOMContentLoaded', () => {
  showStep(currentStep);
  initializeDragDropHandlers(); // ✅ Now this is safe
});
</script>
<script>
window.previousApplication = <?= json_encode($previous_application ?? null) ?>;
window.previousCourseIds = <?= json_encode($course_ids ?? []) ?>;
window.educationFromPHP = <?= json_encode($education_entries ?? []) ?>;
window.experienceFromPHP = <?= json_encode($experience_entries ?? []) ?>;
</script>
<script>
window.previousApplication = <?= json_encode($previous_application ?? null) ?>;
window.previousCourseIds = <?= json_encode($course_ids ?? []) ?>;
window.educationFromPHP = <?= json_encode($education_entries ?? []) ?>;
window.experienceFromPHP = <?= json_encode($experience_entries ?? []) ?>;
</script>

<script>
(function () {
  const restoreModalElement = document.getElementById('restoreModal');
  const restoreBtn = document.getElementById('restoreBtn');

  if (!restoreModalElement || !restoreBtn) return;

  const restoreModal = bootstrap.Modal.getOrCreateInstance(restoreModalElement);
  restoreModal.show();

  if (typeof window.educationEntries === 'undefined') window.educationEntries = [];
  if (typeof window.experienceEntries === 'undefined') window.experienceEntries = [];

  previousCourseIds.forEach(function (id) {
    const checkbox = document.getElementById('course' + id);
    if (checkbox) checkbox.checked = true;
  });

  restoreBtn.addEventListener('click', function () {
    console.log("Restore button clicked ✅");
    if (!previousApplication) return;

    // Step 1
    document.querySelector('input[name="id_card"]').value = previousApplication.id_card || '';
    document.querySelector('input[name="social_security"]').value = previousApplication.social_security || '';
    document.querySelector('input[name="house_phone"]').value = previousApplication.house_phone || '';
    document.querySelector('input[name="university_email"]').value = previousApplication.university_email || '';
    document.querySelector('select[name="gender"]').value = previousApplication.gender || '';
    document.querySelector('select[name="nationality"]').value = previousApplication.nationality || '';

    // Step 2
    document.querySelector('select[name="period_id"]').value = previousApplication.period_id || '';
    previousCourseIds.forEach(function (id) {
      const checkbox = document.getElementById('course' + id);
      if (checkbox) checkbox.checked = true;
    });

    // Step 3 – Education
    if (Array.isArray(window.educationFromPHP)) {
      window.educationEntries = [...window.educationFromPHP];
      if (typeof renderEducationTable === 'function') renderEducationTable();
    }

    // Step 4 – Experience
    if (Array.isArray(window.experienceFromPHP)) {
      window.experienceEntries = [...window.experienceFromPHP];
      if (typeof renderExperienceTable === 'function') renderExperienceTable();
    }

    // Step 6 – Pre-check Responsible Declaration
    const declaration = document.getElementById('responsible_declaration');
    if (declaration) declaration.checked = true;

    // Step 7 – Pre-check GDPR Consent
    const gdpr = document.getElementById('gdpr_consent');
    if (gdpr) gdpr.checked = true;

    // ✅ Show the "Clear Prefilled Data" button
    const clearBtn = document.getElementById('clearPrefillBtn');
    if (clearBtn) clearBtn.style.display = 'inline-block';

    alert("Your previous application details have been restored!");
    showStep(1);
    restoreModal.hide();
  });
})();
</script>
<script>
document.addEventListener('DOMContentLoaded', () => {
  const clearBtn = document.getElementById('clearPrefillBtn');
  if (!clearBtn) return;

  clearBtn.addEventListener('click', () => {
    // Step 1
    ['id_card', 'social_security', 'house_phone', 'university_email'].forEach(name => {
      const input = document.querySelector(`input[name="${name}"]`);
      if (input) input.value = '';
    });

    ['gender', 'nationality'].forEach(name => {
      const select = document.querySelector(`select[name="${name}"]`);
      if (select) select.value = '';
    });

    // Step 2
    const period = document.querySelector('select[name="period_id"]');
    if (period) period.value = '';
    document.querySelectorAll('input[type="checkbox"][name="course_ids[]"]').forEach(cb => cb.checked = false);

    // Step 3 & 4
    if (Array.isArray(window.educationEntries)) window.educationEntries = [];
    if (Array.isArray(window.experienceEntries)) window.experienceEntries = [];
    if (typeof renderEducationTable === 'function') renderEducationTable();
    if (typeof renderExperienceTable === 'function') renderExperienceTable();

    // Step 5 — reset file previews and inputs
    document.querySelectorAll('.drag-drop-area').forEach(area => {
      const input = area.querySelector('input[type="file"]');
      const preview = area.querySelector('.file-preview');
      const removeBtn = area.querySelector('.remove-files-btn');

      if (input) input.value = '';
      if (preview) preview.innerHTML = '<small class="text-muted">No file selected</small>';
      if (removeBtn) removeBtn.style.display = 'none';
    });

    // Remove restored hidden file inputs
    document.querySelectorAll('input[type="hidden"][name$="_existing[]"]').forEach(el => el.remove());

    // Step 6 and 7 — clear any checked boxes
    document.querySelectorAll('input[type="checkbox"]').forEach(cb => {
      if (cb.closest('#step6') || cb.closest('#step7')) cb.checked = false;
    });

    // Hide the clear button again
    clearBtn.style.display = 'none';

    // Return to Step 1
    showStep(1);
    alert("Prefilled data has been cleared.");
  });
});
</script>
</body>
</html>
