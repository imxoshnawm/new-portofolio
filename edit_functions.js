<script src="edit_functions.js"></script>


function initEditForms() {
    // Edit functions for each type
    window.editCertificate = function(id) {
        const addForm = document.querySelector('.add-form');
        const editForm = document.getElementById('editCertificateForm');
        
        if (addForm) addForm.style.display = 'none';
        if (editForm) {
            editForm.style.display = 'block';
            editForm.classList.add('loading');
            
            fetch(`get_item.php?table=certificates&id=${id}`)
                .then(response => response.json())
                .then(data => {
                    // Fill form fields
                    document.getElementById('edit_certificate_id').value = data.id;
                    document.getElementById('edit_certificate_title_en').value = data.title_en || '';
                    document.getElementById('edit_certificate_title_ku').value = data.title_ku || '';
                    document.getElementById('edit_certificate_description_en').value = data.description_en || '';
                    document.getElementById('edit_certificate_description_ku').value = data.description_ku || '';
                    document.getElementById('edit_certificate_issue_date').value = data.issue_date || '';
                    document.getElementById('edit_certificate_org').value = data.issuing_organization || '';
                    
                    // Show current image
                    if (data.image_path) {
                        const imagePreview = document.getElementById('current_certificate_image');
                        if (imagePreview) {
                            imagePreview.innerHTML = `
                                <div class="current-image">
                                    <img src="${data.image_path}" alt="Current certificate" style="max-width: 200px">
                                    <p>Current image</p>
                                </div>`;
                        }
                    }
                    
                    editForm.classList.remove('loading');
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading certificate: ' + error.message);
                    editForm.style.display = 'none';
                    if (addForm) addForm.style.display = 'block';
                });
        }
    };

    window.editExperience = function(id) {
        const addForm = document.querySelector('.add-form');
        const editForm = document.getElementById('editExperienceForm');
        
        if (addForm) addForm.style.display = 'none';
        if (editForm) {
            editForm.style.display = 'block';
            editForm.classList.add('loading');
            
            fetch(`get_item.php?table=experience&id=${id}`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('edit_experience_id').value = data.id;
                    document.getElementById('edit_company_en').value = data.company_en || '';
                    document.getElementById('edit_company_ku').value = data.company_ku || '';
                    document.getElementById('edit_position_en').value = data.position_en || '';
                    document.getElementById('edit_position_ku').value = data.position_ku || '';
                    document.getElementById('edit_description_en').value = data.description_en || '';
                    document.getElementById('edit_description_ku').value = data.description_ku || '';
                    document.getElementById('edit_year').value = data.year || '';
                    document.getElementById('edit_display_order').value = data.display_order || 0;
                    
                    editForm.classList.remove('loading');
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading experience: ' + error.message);
                    editForm.style.display = 'none';
                    if (addForm) addForm.style.display = 'block';
                });
        }
    };

    window.editAchievement = function(id) {
        const addForm = document.querySelector('.add-form');
        const editForm = document.getElementById('editAchievementForm');
        
        if (addForm) addForm.style.display = 'none';
        if (editForm) {
            editForm.style.display = 'block';
            editForm.classList.add('loading');
            
            fetch(`get_item.php?table=achievements&id=${id}`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('edit_achievement_id').value = data.id;
                    document.getElementById('edit_achievement_title_en').value = data.title_en || '';
                    document.getElementById('edit_achievement_title_ku').value = data.title_ku || '';
                    document.getElementById('edit_achievement_description_en').value = data.description_en || '';
                    document.getElementById('edit_achievement_description_ku').value = data.description_ku || '';
                    document.getElementById('edit_achievement_year').value = data.year || '';
                    document.getElementById('edit_achievement_display_order').value = data.display_order || 0;
                    
                    if (data.image_path) {
                        const imagePreview = document.getElementById('current_achievement_image');
                        if (imagePreview) {
                            imagePreview.innerHTML = `
                                <div class="current-image">
                                    <img src="${data.image_path}" alt="Current achievement" style="max-width: 200px">
                                    <p>Current image</p>
                                </div>`;
                        }
                    }
                    
                    editForm.classList.remove('loading');
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading achievement: ' + error.message);
                    editForm.style.display = 'none';
                    if (addForm) addForm.style.display = 'block';
                });
        }
    };

    window.editReport = function(id) {
        const addForm = document.querySelector('.add-form');
        const editForm = document.getElementById('editReportForm');
        
        if (addForm) addForm.style.display = 'none';
        if (editForm) {
            editForm.style.display = 'block';
            editForm.classList.add('loading');
            
            fetch(`get_item.php?table=reports&id=${id}`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('edit_report_id').value = data.id;
                    document.getElementById('edit_report_title_en').value = data.title_en || '';
                    document.getElementById('edit_report_title_ku').value = data.title_ku || '';
                    document.getElementById('edit_report_description_en').value = data.description_en || '';
                    document.getElementById('edit_report_description_ku').value = data.description_ku || '';
                    
                    if (data.file_url) {
                        const filePreview = document.getElementById('current_report_file');
                        if (filePreview) {
                            filePreview.innerHTML = `
                                <div class="current-file">
                                    <p>Current file: <a href="${data.file_url}" target="_blank">View File</a></p>
                                </div>`;
                        }
                    }
                    
                    editForm.classList.remove('loading');
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading report: ' + error.message);
                    editForm.style.display = 'none';
                    if (addForm) addForm.style.display = 'block';
                });
        }
    };

    // Cancel edit form
    window.cancelEdit = function(formId) {
        const editForm = document.getElementById(formId);
        if (editForm) {
            editForm.style.display = 'none';
            editForm.reset();
            const addForm = document.querySelector('.add-form');
            if (addForm) addForm.style.display = 'block';
        }
    };
}

// Initialize when document is ready
document.addEventListener('DOMContentLoaded', initEditForms);
