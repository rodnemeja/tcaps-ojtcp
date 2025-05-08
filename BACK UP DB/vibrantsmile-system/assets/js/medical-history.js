// Medical History Button Click Handler
$(document).ready(function() {
    $('#medicalHistoryBtn').click(function(e) {
        e.preventDefault();
        $('#medicalHistoryModal').modal('show');
    });

    // Toggle allergies details
    $('input[name="has_allergies"]').change(function() {
        $('#allergiesDetails').toggleClass('d-none', $(this).val() !== '1');
    });

    // Toggle medications details
    $('input[name="has_medications"]').change(function() {
        $('#medicationsDetails').toggleClass('d-none', $(this).val() !== '1');
    });

    // Toggle other conditions details
    $('input[name="medical_conditions[]"][value="other"]').change(function() {
        $('#otherConditionsDetails').toggleClass('d-none', !$(this).is(':checked'));
    });

    // Handle medical history form submission
    $('#saveMedicalHistory').click(function() {
        const form = $('#medicalHistoryForm');
        const formData = new FormData(form[0]);

        $.ajax({
            url: 'save_medical_history.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if(response.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Medical History Saved',
                        text: 'You can now proceed with booking your appointment.',
                        confirmButtonColor: '#4e73df'
                    }).then(() => {
                        $('#medicalHistoryModal').modal('hide');
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.message || 'Failed to save medical history. Please try again.',
                        confirmButtonColor: '#4e73df'
                    });
                }
            },
            error: function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'An error occurred while saving your medical history. Please try again.',
                    confirmButtonColor: '#4e73df'
                });
            }
        });
    });
}); 