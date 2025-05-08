<!-- Medical History Modal -->
<div class="modal fade" id="medicalHistoryModal" tabindex="-1" aria-labelledby="medicalHistoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="medicalHistoryModalLabel">Medical History Form</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="medicalHistoryForm">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Do you have any allergies?</label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="has_allergies" value="1" required>
                                <label class="form-check-label">Yes</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="has_allergies" value="0">
                                <label class="form-check-label">No</label>
                            </div>
                            <div id="allergiesDetails" class="mt-2 d-none">
                                <textarea class="form-control" name="allergies_details" rows="2" placeholder="Please specify your allergies"></textarea>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Are you taking any medications?</label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="has_medications" value="1" required>
                                <label class="form-check-label">Yes</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="has_medications" value="0">
                                <label class="form-check-label">No</label>
                            </div>
                            <div id="medicationsDetails" class="mt-2 d-none">
                                <textarea class="form-control" name="medications_details" rows="2" placeholder="Please list your current medications"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-12">
                            <label class="form-label">Medical Conditions (Check all that apply)</label>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="medical_conditions[]" value="diabetes">
                                        <label class="form-check-label">Diabetes</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="medical_conditions[]" value="heart_disease">
                                        <label class="form-check-label">Heart Disease</label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="medical_conditions[]" value="hypertension">
                                        <label class="form-check-label">Hypertension</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="medical_conditions[]" value="asthma">
                                        <label class="form-check-label">Asthma</label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="medical_conditions[]" value="thyroid">
                                        <label class="form-check-label">Thyroid Disease</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="medical_conditions[]" value="other">
                                        <label class="form-check-label">Other</label>
                                    </div>
                                </div>
                            </div>
                            <div id="otherConditionsDetails" class="mt-2 d-none">
                                <textarea class="form-control" name="other_conditions_details" rows="2" placeholder="Please specify other medical conditions"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Additional Notes</label>
                        <textarea class="form-control" name="additional_notes" rows="3" placeholder="Any additional information you'd like to share"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="saveMedicalHistory">Save Medical History</button>
            </div>
        </div>
    </div>
</div> 