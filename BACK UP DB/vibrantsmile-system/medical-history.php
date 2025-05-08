<?php
 require_once "config/database.php";
// Check if the user is logged in, if not then redirect him to login page

?>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Vibrant Smile Dental Clinic</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="" name="keywords">
    <meta content="" name="description">

    <!-- Favicon -->
    <link href="img/favicon.ico" rel="icon">

    <!-- Google Web Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&family=Red+Rose:wght@600;700&display=swap"
        rel="stylesheet">

    <!-- Icon Font Stylesheet -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.4/font/bootstrap-icons.css">



    <!-- Customized Bootstrap Stylesheet -->
    <link href="css1/bootstrap.min.css" rel="stylesheet">

    <!-- Template Stylesheet -->
    <link href="css1/style.css" rel="stylesheet">
    
</head>

<body onload="myFunction()" class="d-flex flex-column min-vh-100">

                        
                    
                        <a class="btn btn-primary ms-2" href="#" data-bs-toggle="modal" data-bs-target="#book">Add History</a>
                    </div>
                </div>
            </nav>
        </div>
    </div>
    <div class="modal fade" id="book" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="exampleModalLabel">Add Medical History</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form method="post" enctype="multipart/form-data" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" style="margin-top: 50px;">
    <div class="modal-body">
        <div class="card elevation-3" style="border-radius: 10px;">
            <div class="card-header text-white text-center" style="background-color:navy; color:white; border-top-left-radius: 10px; border-top-right-radius: 10px;">
                <h3 class="card-title text-white">Declaration Form</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="form-group col-12 text-center mb-3">
                        <hr class="m-0 p-0 mb-2">
                        <label class="font-weight-bold text-dark" style="font-size: 1.2em;">Dental History</label>
                        <hr class="m-0 p-0">
                    </div>
                    <div class="form-group col-12 col-md-6">
                        <label for="prev_dentist">Previous Dentist:</label>
                        <input type="text" name="prev_dentist" id="prev_dentist" class="form-control" />
                        <small class="form-text text-muted fst-italic">Optional</small>
                    </div>
                    <div class="form-group col-12 col-md-6">
                        <label for="last_visit">Last Dental Visit:</label>
                        <input type="text" name="last_visit" id="last_visit" class="form-control" />
                        <small class="form-text text-muted fst-italic">Optional</small>
                    </div>
                </div>

                <div class="row">
                    <div class="form-group col-12 text-center mb-3">
                        <hr class="m-0 p-0 mb-2">
                        <label class="font-weight-bold text-dark" style="font-size: 1.2em;">Medical History</label>
                        <hr class="m-0 p-0">
                    </div>
                    <div class="form-group col-12">
                        <div class="row align-items-center">
                            <div class="col-12 col-md-7">
                                <label class="text-dark">1. Are you in good health?</label>
                            </div>
                            <div class="col-12 col-md-5">
                                <div class="d-flex justify-content-around">
                                    <div class="custom-control custom-radio">
                                        <input class="custom-control-input" type="radio" id="one_yes" name="one" value="Yes" required>
                                        <label for="one_yes" class="custom-control-label">Yes</label>
                                    </div>
                                    <div class="custom-control custom-radio">
                                        <input class="custom-control-input" type="radio" id="one_no" name="one" value="No">
                                        <label for="one_no" class="custom-control-label">No</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <hr class="mt-2">
                    <div class="form-group col-12">
                        <div class="row align-items-center">
                            <div class="col-12 col-md-7">
                                <label class="text-dark">2. Are you under medical treatment now?</label>
                            </div>
                            <div class="col-12 col-md-5">
                                <div class="d-flex justify-content-around">
                                    <div class="custom-control custom-radio">
                                        <input class="custom-control-input" type="radio" id="two_yes" name="two" value="Yes" required>
                                        <label for="two_yes" class="custom-control-label">Yes</label>
                                    </div>
                                    <div class="custom-control custom-radio">
                                        <input class="custom-control-input" type="radio" id="two_no" name="two" value="No">
                                        <label for="two_no" class="custom-control-label">No</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <input type="text" name="two_answer" id="two_answer" class="form-control" placeholder="If so, what is the condition being treated?" />
                    </div>
                    <hr class="mt-2">
                    <div class="form-group col-12">
                        <div class="row align-items-center">
                            <div class="col-12 col-md-7">
                                <label class="text-dark">3. Have you ever had serious illness or surgical operation?</label>
                            </div>
                            <div class="col-12 col-md-5">
                                <div class="d-flex justify-content-around">
                                    <div class="custom-control custom-radio">
                                        <input class="custom-control-input" type="radio" id="three_yes" name="three" value="Yes" required>
                                        <label for="three_yes" class="custom-control-label">Yes</label>
                                    </div>
                                    <div class="custom-control custom-radio">
                                        <input class="custom-control-input" type="radio" id="three_no" name="three" value="No">
                                        <label for="three_no" class="custom-control-label">No</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <input type="text" name="three_answer" id="three_answer" class="form-control" placeholder="If so, what illness or operation?" />
                    </div>
                    <hr class="mt-2">
                    <div class="form-group col-12">
                        <div class="row align-items-center">
                            <div class="col-12 col-md-7">
                                <label class="text-dark">4. Have you ever been hospitalized?</label>
                            </div>
                            <div class="col-12 col-md-5">
                                <div class="d-flex justify-content-around">
                                    <div class="custom-control custom-radio">
                                        <input class="custom-control-input" type="radio" id="four_yes" name="four" value="Yes" required>
                                        <label for="four_yes" class="custom-control-label">Yes</label>
                                    </div>
                                    <div class="custom-control custom-radio">
                                        <input class="custom-control-input" type="radio" id="four_no" name="four" value="No">
                                        <label for="four_no" class="custom-control-label">No</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <input type="text" name="four_answer" id="four_answer" class="form-control" placeholder="If so, when and why?" />
                    </div>
                    <hr class="mt-2">
                    <div class="form-group col-12">
                        <div class="row align-items-center">
                            <div class="col-12 col-md-7">
                                <label class="text-dark">5. Are you taking any prescription/non-prescription medication?</label>
                            </div>
                            <div class="col-12 col-md-5">
                                <div class="d-flex justify-content-around">
                                    <div class="custom-control custom-radio">
                                        <input class="custom-control-input" type="radio" id="five_yes" name="five" value="Yes" required>
                                        <label for="five_yes" class="custom-control-label">Yes</label>
                                    </div>
                                    <div class="custom-control custom-radio">
                                        <input class="custom-control-input" type="radio" id="five_no" name="five" value="No">
                                        <label for="five_no" class="custom-control-label">No</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <input type="text" name="five_answer" id="five_answer" class="form-control" placeholder="If so, please specify" />
                    </div>
                    <hr class="mt-2">
                    <div class="form-group col-12">
                        <div class="row align-items-center">
                            <div class="col-12 col-md-7">
                                <label class="text-dark">6. Do you use tobacco products?</label>
                            </div>
                            <div class="col-12 col-md-5">
                                <div class="d-flex justify-content-around">
                                    <div class="custom-control custom-radio">
                                        <input class="custom-control-input" type="radio" id="six_yes" name="six" value="Yes" required>
                                        <label for="six_yes" class="custom-control-label">Yes</label>
                                    </div>
                                    <div class="custom-control custom-radio">
                                        <input class="custom-control-input" type="radio" id="six_no" name="six" value="No">
                                        <label for="six_no" class="custom-control-label">No</label>
                                    </div>
                                </div>
                            </div>    
                        </div>
                    </div>
                    <hr class="mt-2">
                    <div class="form-group col-12">
                        <div class="row align-items-center">
                            <div class="col-12 col-md-7">
                                <label class="text-dark">7. Do you use alcohol, cocaine or other dangerous drugs?</label>
                            </div>
                            <div class="col-12 col-md-5">
                                <div class="d-flex justify-content-around">
                                    <div class="custom-control custom-radio">
                                        <input class="custom-control-input" type="radio" id="seven_yes" name="seven" value="Yes" required>
                                        <label for="six_yes" class="custom-control-label">Yes</label>
                                    </div>
                                    <div class="custom-control custom-radio">
                                        <input class="custom-control-input" type="radio" id="seven_no" name="seven" value="No">
                                        <label for="six_no" class="custom-control-label">No</label>
                                    </div>
                                </div>
                            </div>    
                        </div>
                    </div>
                    <hr class="mt-2">
                    <div class="form-group col-12">
                        <label class="text-dark">8. Are you allergic to any of the following:</label>
                        <div class="row justify-content-between pl-2 pr-2">
                            <div class="custom-control custom-checkbox">
                                <input class="custom-control-input" type="checkbox" id="local_anesthetic" name="local_anesthetic">
                                <label for="local_anesthetic" class="custom-control-label">Local Anesthetic (ex. Lidocaine)</label>
                            </div>
                            <div class="custom-control custom-checkbox">
                                <input class="custom-control-input" type="checkbox" id="penicilin" name="penicilin">
                                <label for="penicilin" class="custom-control-label">Penicilin, Antibiotics</label>
                            </div>
                        </div>
                        
                        <div class="row justify-content-between pl-2 pr-2">
                            <div class="custom-control custom-checkbox">
                                <input class="custom-control-input" type="checkbox" id="aspinn" name="aspinn">
                                <label for="aspinn" class="custom-control-label">Aspirin</label>
                            </div>
                            <div class="custom-control custom-checkbox">
                                <input class="custom-control-input" type="checkbox" id="latex" name="latex">
                                <label for="latex" class="custom-control-label">Latex</label>
                            </div>
                        </div>
                        <div class="row justify-content-between pl-2 pr-2">
                            <div class="custom-control custom-checkbox">
                                <input class="custom-control-input" type="checkbox" id="sulfa_drugs" name="sulfa_drugs">
                                <label for="sulfa_drugs" class="custom-control-label">Sulfa drugs</label>
                            </div>
                            <div class="row">
                                <div class="custom-control custom-checkbox mr-1">
                                    <input class="custom-control-input" type="checkbox" id="others" name="others">
                                    <label for="others" class="custom-control-label">Others</label>
                                </div>
                                <div class="">
                                    <input type="text" name="others_answer" id="others_answer" class="form-control" placeholder="Please specify" />
                                </div>
                            </div>
                        </div>
                    </div>
                    <hr class="mt-2">
                    <div class="form-group col-12">
                        <label class="text-dark">9. For women only:</label>
                        <div class="row mb-3">
                            <div class="col-12 col-md-7">
                                <label class="text-dark">Are you pregnant?</label>
                            </div>
                            <div class="col-12 col-md-5">
                                <div class="row justify-content-around">
                                    <div class="custom-control custom-radio">
                                        <input class="custom-control-input" type="radio" id="pregnant_yes" name="pregnant" value="Yes" required>
                                        <label for="pregnant_yes" class="custom-control-label">Yes</label>
                                    </div>
                                    <div class="custom-control custom-radio">
                                        <input class="custom-control-input" type="radio" id="pregnant_no" name="pregnant" value="No">
                                        <label for="pregnant_no" class="custom-control-label">No</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-12 col-md-7">
                                <label class="text-dark" >Are you nursing?</label>
                            </div>
                            <div class="col-12 col-md-5">
                                <div class="row justify-content-around">
                                    <div class="custom-control custom-radio">
                                        <input class="custom-control-input" type="radio" id="nursing_yes" name="nursing" value="Yes" required>
                                        <label for="nursing_yes" class="custom-control-label">Yes</label>
                                    </div>
                                    <div class="custom-control custom-radio">
                                        <input class="custom-control-input" type="radio" id="nursing_no" name="nursing" value="No">
                                        <label for="nursing_no" class="custom-control-label">No</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-12 col-md-7">
                                <label class="text-dark">Are you taking birth control pills?</label>
                            </div>
                            <div class="col-12 col-md-5">
                                <div class="row justify-content-around">
                                    <div class="custom-control custom-radio">
                                        <input class="custom-control-input" type="radio" id="pills_yes" name="pills" value="Yes" required>
                                        <label for="pills_yes" class="custom-control-label">Yes</label>
                                    </div>
                                    <div class="custom-control custom-radio">
                                        <input class="custom-control-input" type="radio" id="pills_no" name="pills" value="No">
                                        <label for="pills_no" class="custom-control-label">No</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- <div class="form-group col-12">
                        <div class="row">
                            <div class="col-12 col-md-4">
                                <label class="text-dark">10. Blood Type:</label>
                            </div>
                            <div class="col-12 col-md-8">
                                <input type="text" name="blood_type" id="blood_type" class="form-control" placeholder="Enter your blood type" />
                            </div>
                        </div>
                    </div> -->
                    <hr class="mt-2">
                    <div class="form-group col-12">
                        <div class="row">
                            <div class="col-12 col-md-4">
                                <label class="text-dark">10. Blood Type:</label>
                            </div>
                            <div class="col-12 col-md-8">
                                <select name="blood_type" id="blood_type" class="form-control">
                                    <option value="" disabled selected>Select your blood type</option>
                                    <option value="A+">A+</option>
                                    <option value="A-">A-</option>
                                    <option value="B+">B+</option>
                                    <option value="B-">B-</option>
                                    <option value="AB+">AB+</option>
                                    <option value="AB-">AB-</option>
                                    <option value="O+">O+</option>
                                    <option value="O-">O-</option>
                                    <option value="Unknown">Unknown</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <hr class="mt-2">
                    <div class="form-group col-12">
                        <label class="text-dark">11. Do you have or have you had any of the following? Check which apply:</label>
                        <div class="row col-12">
                            <div class="col-12 col-md-6 custom-control custom-checkbox">
                                <input class="custom-control-input" type="checkbox" id="high_blood" name="high_blood">
                                <label for="high_blood" class="custom-control-label">High Blood Pressure</label>
                            </div>
                            <div class="col-12 col-md-6 custom-control custom-checkbox">
                                <input class="custom-control-input" type="checkbox" id="hepatitis" name="hepatitis">
                                <label for="hepatitis" class="custom-control-label">Hepatitis/Jaundice</label>
                            </div>
                        </div>
                        <div class="row col-12">
                            <div class="col-12 col-md-6 custom-control custom-checkbox">
                                <input class="custom-control-input" type="checkbox" id="low_blood" name="low_blood">
                                <label for="low_blood" class="custom-control-label">Low Blood Pressure</label>
                            </div>
                            <div class="col-12 col-md-6 custom-control custom-checkbox">
                                <input class="custom-control-input" type="checkbox" id="tuberculosis" name="tuberculosis">
                                <label for="tuberculosis" class="custom-control-label">Tuberculosis</label>
                            </div>
                        </div>
                        <div class="row col-12">
                            <div class="col-12 col-md-6 custom-control custom-checkbox">
                                <input class="custom-control-input" type="checkbox" id="epilepsy" name="epilepsy">
                                <label for="epilepsy" class="custom-control-label">Epilepsy/Convulsions</label>
                            </div>
                            <div class="col-12 col-md-6 custom-control custom-checkbox">
                                <input class="custom-control-input" type="checkbox" id="swolen" name="swolen">
                                <label for="swolen" class="custom-control-label">Swollen Ankles/Kidney Disease</label>
                            </div>
                        </div>
                        <div class="row col-12">
                            <div class="col-12 col-md-6 custom-control custom-checkbox">
                                <input class="custom-control-input" type="checkbox" id="aids" name="aids">
                                <label for="aids" class="custom-control-label">AIDS or HIV Infection</label>
                            </div>
                            <div class="col-12 col-md-6 custom-control custom-checkbox">
                                <input class="custom-control-input" type="checkbox" id="asthma" name="asthma">
                                <label for="asthma" class="custom-control-label">Asthma</label>
                            </div>
                        </div>
                        <div class="row col-12">
                            <div class="col-12 col-md-6 custom-control custom-checkbox">
                                <input class="custom-control-input" type="checkbox" id="sexually" name="sexually">
                                <label for="sexually" class="custom-control-label">Sexually Transmitted Disease</label>
                            </div>
                            <div class="col-12 col-md-6 custom-control custom-checkbox">
                                <input class="custom-control-input" type="checkbox" id="emphysema" name="emphysema">
                                <label for="emphysema" class="custom-control-label">Emphysema</label>
                            </div>
                        </div>
                        <div class="row col-12">
                            <div class="col-12 col-md-6 custom-control custom-checkbox">
                                <input class="custom-control-input" type="checkbox" id="ulcer" name="ulcer">
                                <label for="ulcer" class="custom-control-label">Stomach Troubles/Ulcers</label>
                            </div>
                            <div class="col-12 col-md-6 custom-control custom-checkbox">
                                <input class="custom-control-input" type="checkbox" id="bleeding_problem" name="bleeding_problem">
                                <label for="bleeding_problem" class="custom-control-label">Bleeding Problems</label>
                            </div>
                        </div>
                        <div class="row col-12">
                            <div class="col-12 col-md-6 custom-control custom-checkbox">
                                <input class="custom-control-input" type="checkbox" id="fainting_sozure" name="fainting_sozure">
                                <label for="fainting_sozure" class="custom-control-label">Fainting Seizures</label>
                            </div>
                            <div class="col-12 col-md-6 custom-control custom-checkbox">
                                <input class="custom-control-input" type="checkbox" id="blood_diseases" name="blood_diseases">
                                <label for="blood_diseases" class="custom-control-label">Blood Diseases</label>
                            </div>
                        </div>
                        <div class="row col-12">
                            <div class="col-12 col-md-6 custom-control custom-checkbox">
                                <input class="custom-control-input" type="checkbox" id="rapid_weight" name="rapid_weight">
                                <label for="rapid_weight" class="custom-control-label">Rapid Weight Loss</label>
                            </div>
                            <div class="col-12 col-md-6 custom-control custom-checkbox">
                                <input class="custom-control-input" type="checkbox" id="head_injuries" name="head_injuries">
                                <label for="head_injuries" class="custom-control-label">Head Injuries</label>
                            </div>
                        </div>
                        <div class="row col-12">
                            <div class="col-12 col-md-6 custom-control custom-checkbox">
                                <input class="custom-control-input" type="checkbox" id="radiation_therapy" name="radiation_therapy">
                                <label for="radiation_therapy" class="custom-control-label">Radiation Therapy</label>
                            </div>
                            <div class="col-12 col-md-6 custom-control custom-checkbox">
                                <input class="custom-control-input" type="checkbox" id="angina_arthritis" name="angina_arthritis">
                                <label for="angina_arthritis" class="custom-control-label">Angina/Arthritis/Rheumatism</label>
                            </div>
                        </div>
                        <div class="row col-12">
                            <div class="col-12 col-md-6 custom-control custom-checkbox">
                                <input class="custom-control-input" type="checkbox" id="joint_replacement" name="joint_replacement">
                                <label for="joint_replacement" class="custom-control-label">Joint Replacement/Implant</label>
                            </div>
                            <div class="col-12 col-md-6 custom-control custom-checkbox">
                                <input class="custom-control-input" type="checkbox" id="heart_surgery" name="heart_surgery">
                                <label for="heart_surgery" class="custom-control-label">Heart Surgery</label>
                            </div>
                        </div>
                        <div class="row col-12">
                            <div class="col-12 col-md-6 custom-control custom-checkbox">
                                <input class="custom-control-input" type="checkbox" id="heart_attack" name="heart_attack">
                                <label for="heart_attack" class="custom-control-label">Heart Attack</label>
                            </div>
                            <div class="col-12 col-md-6 custom-control custom-checkbox">
                                <input class="custom-control-input" type="checkbox" id="heart_disease" name="heart_disease">
                                <label for="heart_disease" class="custom-control-label">Heart Disease</label>
                            </div>
                        </div>
                        <div class="row col-12">
                            <div class="col-12 col-md-6 custom-control custom-checkbox">
                                <input class="custom-control-input" type="checkbox" id="thyroid" name="thyroid">
                                <label for="thyroid" class="custom-control-label">Thyroid Problem</label>
                            </div>
                            <div class="col-12 col-md-6 custom-control custom-checkbox">
                                <input class="custom-control-input" type="checkbox" id="heart_murmur" name="heart_murmur">
                                <label for="heart_murmur" class="custom-control-label">Heart Murmur</label>
                            </div>
                        </div>
                        <div class="row col-12">
                            <div class="col-12 col-md-6 custom-control custom-checkbox">
                                <input class="custom-control-input" type="checkbox" id="liver_disease" name="liver_disease">
                                <label for="liver_disease" class="custom-control-label">Hepatitis/Liver Disease</label>
                            </div>
                            <div class="col-12 col-md-6 custom-control custom-checkbox">
                                <input class="custom-control-input" type="checkbox" id="rheumatic_faver" name="rheumatic_faver">
                                <label for="rheumatic_faver" class="custom-control-label">Rheumatic Fever</label>
                            </div>
                        </div>
                        <div class="row col-12">
                            <div class="col-12 col-md-6 custom-control custom-checkbox">
                                <input class="custom-control-input" type="checkbox" id="hay_faver" name="hay_faver">
                                <label for="hay_faver" class="custom-control-label">Hay Fever/Allergies</label>
                            </div>
                            <div class="col-12 col-md-6 custom-control custom-checkbox">
                                <input class="custom-control-input" type="checkbox" id="respvalory" name="respvalory">
                                <label for="respvalory" class="custom-control-label">Respiratory Problems</label>
                            </div>
                        </div>
                        <div class="row col-12">
                            <div class="col-12 col-md-6 custom-control custom-checkbox">
                                <input class="custom-control-input" type="checkbox" id="daboles" name="daboles">
                                <label for="daboles" class="custom-control-label">Diabetes</label>
                            </div>
                            <div class="col-12 col-md-6 custom-control custom-checkbox">
                                <input class="custom-control-input" type="checkbox" id="chul_pain" name="chul_pain">
                                <label for="chul_pain" class="custom-control-label">Chest Pain/Stroke</label>
                            </div>
                        </div>
                        <div class="row col-12">
                            <div class="col-12 col-md-6 custom-control custom-checkbox">
                                <input class="custom-control-input" type="checkbox" id="cancer" name="cancer">
                                <label for="cancer" class="custom-control-label">Cancer/Tumors</label>
                            </div>
                            <div class="col-12 col-md-6 custom-control custom-checkbox">
                                <input class="custom-control-input" type="checkbox" id="anomia" name="anomia">
                                <label for="anomia" class="custom-control-label">Anemia</label>
                            </div>
                        </div>
                        <div class="row col-12">
                            <div class="custom-control custom-checkbox mr-1">
                                <input class="custom-control-input" type="checkbox" id="others_two" name="others_two">
                                <label for="others_two" class="custom-control-label">Others</label>
                            </div>
                            <div>
                                <input type="text" name="others_two_answer" id="others_two_answer" class="form-control" placeholder="Please specify"/>
                                <input type="hidden" name="u_id" value="<?php echo $u_id;?>">
                            </div>
                        </div>
                    </div>



                </div>
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal" aria-label="Close">Close</button>
        <button class="btn btn-danger" name="Send">Save Data</button>
    </div>
</form>
        <?php 
        if(isset($_POST['Send'])){
            
            // Collect form data
            $one = $_POST['one'];
            $two = $_POST['two'];
            $two_answer = $_POST['two_answer'];
            $three = $_POST['three'];
            $three_answer = $_POST['three_answer'];
            $four = $_POST['four'];
            $four_answer = $_POST['four_answer'];
            $five = $_POST['five'];
            $five_answer = $_POST['five_answer'];
            $six = $_POST['six'];
            $seven = $_POST['seven'];
            $local_anesthetic = isset($_POST['local_anesthetic']) ? 'Local Anesthetic' : '';
            $penicilin = isset($_POST['penicilin']) ? 'Penicilin, Antibiotics' : '';
            $aspinn = isset($_POST['aspinn']) ? 'Aspinn' : '';
            $latex = isset($_POST['latex']) ? 'Latex' : '';
            $sulfa_drugs = isset($_POST['sulfa_drugs']) ? 'Sulfa drugs' : '';
            $others = isset($_POST['others']) ? 'Others' : '';
            $others_answer = $_POST['others_answer'];
            $pregnant = $_POST['pregnant'];
            $nursing = $_POST['nursing'];
            $pills = $_POST['pills'];
            $blood_type = $_POST['blood_type'];
            
            // Process multiple selections for questions 8, 9, and 11
            $allergies = [];
            if ($local_anesthetic) $allergies[] = $local_anesthetic;
            if ($penicilin) $allergies[] = $penicilin;
            if ($aspinn) $allergies[] = $aspinn;
            if ($latex) $allergies[] = $latex;
            if ($sulfa_drugs) $allergies[] = $sulfa_drugs;
            if ($others) $allergies[] = $others . ': ' . $others_answer;
            $allergies_str = implode(', ', $allergies);
            
            $conditions = [];
            if (isset($_POST['high_blood'])) $conditions[] = 'High Blood Pressure';
            if (isset($_POST['hepatitis'])) $conditions[] = 'Hepatitis/Jaundice';
            if (isset($_POST['low_blood'])) $conditions[] = 'Low Blood Pressure';
            if (isset($_POST['tuberculosis'])) $conditions[] = 'Tuberculosis';
            if (isset($_POST['epilepsy'])) $conditions[] = 'Epilepsy/Convulsions';
            if (isset($_POST['swolen'])) $conditions[] = 'Swolen ankles/Kidney disease';
            if (isset($_POST['aids'])) $conditions[] = 'AIDS or HIV Infection';
            if (isset($_POST['asthma'])) $conditions[] = 'Asthma';
            if (isset($_POST['sexually'])) $conditions[] = 'Sexually Transmitted disease';
            if (isset($_POST['emphysema'])) $conditions[] = 'Emphysema';
            if (isset($_POST['ulcer'])) $conditions[] = 'Stomach Troubles/Ulcers';
            if (isset($_POST['bleeding_problem'])) $conditions[] = 'Bleeding Problems';
            if (isset($_POST['fainting_sozure'])) $conditions[] = 'Fainting Seizure';
            if (isset($_POST['blood_diseases'])) $conditions[] = 'Blood Diseases';
            if (isset($_POST['rapid_weight'])) $conditions[] = 'Rapid Weight Loss';
            if (isset($_POST['head_injuries'])) $conditions[] = 'Head Injuries';
            if (isset($_POST['radiation_therapy'])) $conditions[] = 'Radiation Therapy';
            if (isset($_POST['angina_arthritis'])) $conditions[] = 'Angina/Arthritis/Rheumatism';
            if (isset($_POST['joint_replacement'])) $conditions[] = 'Joint Replacement/Implant';
            if (isset($_POST['heart_surgery'])) $conditions[] = 'Heart Surgery';
            if (isset($_POST['heart_attack'])) $conditions[] = 'Heart Attack';
            if (isset($_POST['heart_disease'])) $conditions[] = 'Heart Disease';
            if (isset($_POST['thyroid'])) $conditions[] = 'Thyroid Problem';
            if (isset($_POST['heart_murmur'])) $conditions[] = 'Heart Murmur';
            if (isset($_POST['liver_disease'])) $conditions[] = 'Hepatitis/Liver Disease';
            if (isset($_POST['rheumatic_faver'])) $conditions[] = 'Rheumatic Fever';
            if (isset($_POST['hay_faver'])) $conditions[] = 'Hay Fever/Allergies';
            if (isset($_POST['respvalory'])) $conditions[] = 'Respiratory Problems';
            if (isset($_POST['daboles'])) $conditions[] = 'Diabetes';
            if (isset($_POST['chul_pain'])) $conditions[] = 'Chest Pain/Stroke';
            if (isset($_POST['cancer'])) $conditions[] = 'Cancer/Tumors';
            if (isset($_POST['anomia'])) $conditions[] = 'Anemia';
            if (isset($_POST['others_two'])) $conditions[] = 'Others: ' . $_POST['others_two_answer'];
            $conditions_str = implode(', ', $conditions);
            $date = date('F d,Y H:i: A',time());
            $u_id = $_POST['u_id'];
            $prev_dentist = $_POST['prev_dentist'];
            $last_visit= $_POST['last_visit'];
// Insert data into the database
$sql_sched1 = "INSERT INTO medical_history VALUES (null,'$date','$prev_dentist','$last_visit','$one', '$two', '$two_answer', '$three', '$three_answer', '$four', '$four_answer', '$five', '$five_answer', '$six', '$seven', '$allergies_str', '$pregnant', '$nursing', '$pills', '$blood_type', '$conditions_str','$u_id')";
	
				if (mysqli_query($conn, $sql_sched1)){
                                    echo '<script>
    									function myFunction() {
    										swal({
    										title: "Success!",
    										text: "Your Data Successfully Saved",
    									    icon: "success",
    										type: "success"
    										}).then(function() {
    										window.location = "medical-history.php";
    									  });}
    								</script>';
                }else{
                                    echo '<script>
    									function myFunction() {
    									swal({
    									title: "Failed!",
    									text: "Please Try Again",
    									icon: "error",
    									button: "Ok",
    									});}
    								</script>';
                }
        }
        ?>
      </div>
    </div>
    </div>
    <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="exampleModalLabel">Logout</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
            <center>Do you want to Signout?</center>
        </div>
        <div class="modal-footer">
            <button  class="btn btn-secondary"  data-bs-dismiss="modal" aria-label="Close">Close</button>
            <a  href="logout.php" class="btn btn-danger" >Logout</a>
        </div>
      </div>
    </div>
    </div>
    
    <div class="modal fade" id="settingsModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="exampleModalLabel">Settings</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div> <form  method="post" enctype = "multipart/form-data" action = "<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" style="margin-top:50px;">
        <div class="modal-body">
                <div class = "form-group row">
                    <div class="col-sm-12 mb-3 mb-sm-0">
                        <label>Email</label>
                        <input type="email" class="form-control form-control-user" name = "email" value="<?php echo $f['email'];?>"  required>
                        <input type="hidden" class="form-control form-control-user" name = "user_id"  autofocus value="<?php echo $u_id?>" required>
                    </div>     
                </div>
                <div class = "form-group row">
                    <div class="col-sm-4 mb-3 mb-sm-0">
                        <label>First Name</label>
                        <input type="text" class="form-control form-control-user" name = "fname" value="<?php echo $f['fname'];?>"  required>
                    </div>     
                    <div class="col-sm-4 mb-3 mb-sm-0">
                        <label>Middle Name</label>
                        <input type="text" class="form-control form-control-user" name = "mname" value="<?php echo $f['mname'];?>"  required>
                    </div>     
                    <div class="col-sm-4 mb-3 mb-sm-0">
                        <label>Last Name</label>
                        <input type="text" class="form-control form-control-user" name = "lname" value="<?php echo $f['lname'];?>"  required>
                    </div>     
                </div>
                <div class = "form-group row">
                    <div class="col-sm-4 mb-3 mb-sm-0">
                        <label>Password</label>
                        <input type="password" class="form-control form-control-user" name = "password" >
                        <input type="hidden" class="form-control form-control-user" name = "password_current" value="<?php echo $f['password'];?>"  >
                    </div>     
                    <div class="col-sm-4 mb-3 mb-sm-0">
                        <label>Confirm</label>
                        <input type="password" class="form-control form-control-user" name = "cpassword"  >
                    </div>     
                    <div class="col-sm-4 mb-3 mb-sm-0">
                        <label>Current Password</label>
                        <input type="password" class="form-control form-control-user" name = "current_password"  required>
                    </div>     
                </div>
        </div>
        <div class="modal-footer">
            <button  class="btn btn-secondary"  data-bs-dismiss="modal" aria-label="Close">Close</button>
            <button  class="btn btn-danger" name="update_data">Update Data</button>
        </div>
        </form>
        <?php
            if(isset($_POST['update_data'])){
                $email   = $_POST['email'];
                $user_id = $_POST['user_id'];
                $fname   = $_POST['fname'];
                $mname   = $_POST['mname'];
                $lname   = $_POST['lname'];
                $password    = $_POST['password'];
                $cpassword   = $_POST['cpassword'];
                $password_current   = $_POST['password_current'];
                $current_password = $_POST['current_password'];
                if($password==null){
                    $password_update = $password_current;
                    $update = $conn->query("UPDATE `user_account1` SET `email` = '$email',`fname`='$fname',`mname`='$mname',`lname`='$lname',`password`='$password_update' WHERE `u_id` = '$user_id'");
                    
    if ($update) {
        echo '<script>
                function myFunction() {
                    swal({
                        title: "Success! ",
                        text: "Successfully Updated Sign In Again",
                        icon: "success",
                        type: "success"
                    }).then(function() {
                        window.location = "logout.php";
                    });
                }
              </script>';
    } else {
        echo '<script>
                function myFunction() {
                    swal({
                        title: "Failed!",
                        text: "Please Try Again",
                        icon: "error",
                        button: "Ok",
                    });
                }
              </script>';
    }
                }elseif($password==$cpassword){
                     if(password_verify($current_password, $password_current)){
                         $password_update = password_hash($password, PASSWORD_DEFAULT);
                         $update = $conn->query("UPDATE `user_account1` SET `email` = '$email',`fname`='$fname',`mname`='$mname',`lname`='$lname',`password`='$password_update' WHERE `u_id` = '$user_id'");
                         
    if ($update) {
        echo '<script>
                function myFunction() {
                    swal({
                        title: "Success! ",
                        text: "Successfully Updated Sign In Again",
                        icon: "success",
                        type: "success"
                    }).then(function() {
                        window.location = "logout.php";
                    });
                }
              </script>';
    } else {
        echo '<script>
                function myFunction() {
                    swal({
                        title: "Failed!",
                        text: "Please Try Again",
                        icon: "error",
                        button: "Ok",
                    });
                }
              </script>';
    }
                     }else{
                                echo '<script>
                                function myFunction() {
                                    swal({
                                        title: "Failed!",
                                        text: "Please Try Again",
                                        icon: "error",
                                        button: "Ok",
                                    });
                                }
                               </script>';
                     }
                }else{
                    
                                echo '<script>
                                function myFunction() {
                                    swal({
                                        title: "Failed!",
                                        text: "Mismatch Password",
                                        icon: "error",
                                        button: "Ok",
                                    });
                                }
                               </script>';
                }
                
                }
                
                    
                
            
        ?>
      </div>
    </div>
    </div>
    <!-- Service Start -->
    <div class="container-fluid container-service py-5">
    <div class="container pt-5">
        <div class="text-center mx-auto wow fadeInUp" data-wow-delay="0.1s" style="max-width: 600px;">
            <h1 class="display-6 mb-3"> Medical History</h1>
        </div>
        <div class="row g-4">
            <?php
                $q_e1 = $conn->query("SELECT * FROM `medical_history` WHERE `u_id`='$u_id'");
                while($f_e1 = $q_e1->fetch_array()){
            ?>
           <div class="col-lg-4 col-md-6 wow fadeInUp" data-wow-delay="0.7s">
                <div class="service-item">
                    <div class="icon-box-primary mb-4">
                    <i class="bi bi-journal-medical text-dark"></i>
                    </div>
                    <h5 class="mb-3"><?php echo $f_e1['med_prev_dent']; ?></h5>
                    <p class="mb-4"><?php echo $f_e1['med_date']; ?></p>
                    <!-- View Button -->
                    <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#historyModal<?php echo $f_e1['med_id']; ?>">View</button>
                    <!--- Edit Button -->
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $f_e1['med_id']; ?>">Update</button>
                    <!-- dELETE Button -->
                     <!-- <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $f_e1['med_id']; ?>">Delete</button> -->
                </div>
            </div>
            <!-- view modal history of appointment -->

            <!-- History Modal (unique to each med_id) -->
            <div class="modal fade" id="historyModal<?php echo $f_e1['med_id']; ?>" tabindex="-1" aria-labelledby="historyModalLabel<?php echo $f_e1['med_id']; ?>" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="historyModalLabel<?php echo $f_e1['med_id']; ?>">Medical History</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <!-- <div class="modal-body">
                            <p class="text-center" ><strong>General Information</strong></p>
                            <p><strong>Previous Dentist:</strong> <?php echo $f_e1['med_prev_dent']; ?></p>
                            <p><strong>Last Dental Visit:</strong> <?php echo $f_e1['med_last_vi']; ?></p>
                            <p><strong>Date:</strong> <?php echo $f_e1['med_date']; ?></p>
                            <br>
                            <br>
                            <p class="text-center" ><strong>Health Questions</strong></p>
                            <p><strong>1. Are you in good health?</strong> <?php echo $f_e1['one']; ?></p>
                            <p><strong>2. Are you under medical treatment now?</strong> <?php echo $f_e1['two']; ?></p>
                            <p><strong>3. Have you ever had a serious illness or surgical operation?</strong> <?php echo $f_e1['three']; ?></p>
                            <p><strong>3.1 If so, what illness or operation?</strong> <?php echo $f_e1['three_answer']; ?></p>
                            <p><strong>4. Have you ever been hospitalized?</strong> <?php echo $f_e1['four']; ?></p>
                            <p><strong>4.1 If so, when and why?</strong> <?php echo $f_e1['four_answer']; ?></p>
                            <p><strong>5. Are you taking any prescription/non-prescription medication?</strong> <?php echo $f_e1['five']; ?></p>
                            <p><strong>5.1 If so, please specify:</strong> <?php echo $f_e1['five_answer']; ?></p>
                            <p><strong>6. Do you use tobacco products?</strong> <?php echo $f_e1['six']; ?></p>
                            <p><strong>7. Do you use alcohol, cocaine, or other dangerous drugs?</strong> <?php echo $f_e1['seven']; ?></p>
                            <br>
                            <br>
                            <p class="text-center" ><strong>Additional Information</strong></p>
                            <p><strong>Allergies</strong> <?php echo $f_e1['allergies_str']; ?></p>
                            <p><strong>Pregnant</strong> <?php echo $f_e1['pregnant']; ?></p>
                            <p><strong>Nursing</strong> <?php echo $f_e1['nursing']; ?></p>
                            <p><strong>Pills</strong> <?php echo $f_e1['pills']; ?></p>
                            <p><strong>Blood Type</strong> <?php echo $f_e1['blood_type']; ?></p>
                            <p><strong>Conditions</strong> <?php echo $f_e1['conditions_str']; ?></p>

                        </div> -->
                        
                        <!-- new ui try for view -->

                <div class="card mb-3 shadow-sm">
                <div class="card-header bg-primary text-white"><strong>Medical History</strong></div>
                <div class="card-body">
                
                <!-- General Information -->
                <h5 class="card-title">General Information</h5>
                <table class="table table-striped table-bordered">
                <tr><th class="fixed-width">Date</th><td><?php echo $f_e1['med_date']; ?></td></tr>
                <tr><th class="fixed-width">Previous Dentist</th><td><?php echo $f_e1['med_prev_dent']; ?></td></tr>
                <tr><th class="fixed-width">Last Visit</th><td><?php echo $f_e1['med_last_vi']; ?></td></tr>
                </table>'
                
                 <!-- Health Questions -->
                <h5 class="card-title mt-4">Health Questions</h5>
                <table class="table table-striped table-bordered">
                <tr><th class="fixed-width">1. Are you in good health?</th><td><span class="answer-highlight"><?php echo $f_e1['one']; ?></span></td></tr>
                <tr><th class="fixed-width">2. Are you under medical treatment now?</th><td><span class="answer-highlight"><?php echo $f_e1['two']; ?></span></td></tr>
                <tr><th class="fixed-width">If so, what is the condition being treated?</th><td><span class="answer-highlight"><?php echo $f_e1['two_answer']; ?></span></td></tr>
                <tr><th class="fixed-width">3. Have you ever had a serious illness or surgical operation?</th><td><span class="answer-highlight"><?php echo $f_e1['three']; ?></span></td></tr>
                <tr><th class="fixed-width">If so, what illness or operation?</th><td><span class="answer-highlight"><?php echo $f_e1['three_answer']; ?></span></td></tr>
                <tr><th class="fixed-width">4. Have you ever been hospitalized?</th><td><span class="answer-highlight"><?php echo $f_e1['four']; ?></span></td></tr>
                <tr><th class="fixed-width">If so, when and why?</th><td><span class="answer-highlight"><?php echo $f_e1['four_answer']; ?></span></td></tr>
                <tr><th class="fixed-width">5. Are you taking any prescription/non-prescription medication?</th><td><span class="answer-highlight"><?php echo $f_e1['five']; ?></span></td></tr>
                <tr><th class="fixed-width">If so, please specify:</th><td><span class="answer-highlight"><?php echo $f_e1['five_answer']; ?></span></td></tr>
                <tr><th class="fixed-width">6. Do you use tobacco products?</th><td><span class="answer-highlight"><?php echo $f_e1['six']; ?></span></td></tr>
                <tr><th class="fixed-width">7. Do you use alcohol, cocaine, or other dangerous drugs?</th><td><span class="answer-highlight"><?php echo $f_e1['seven']; ?></span></td></tr>
                </table>
                
                <!-- Additional Information -->
                <h5 class="card-title mt-4">Additional Information</h5>
                <table class="table table-striped table-bordered">
                <tr><th class="fixed-width">Allergies</th><td><span class="answer-highlight"><?php echo $f_e1['allergies_str']; ?></span></td></tr>
                <tr><th class="fixed-width">Pregnant</th><td><span class="answer-highlight"> <?php echo $f_e1['pregnant']; ?></span></td></tr>
                <tr><th class="fixed-width">Nursing</th><td><span class="answer-highlight"><?php echo $f_e1['nursing']; ?></span></td></tr>
                <tr><th class="fixed-width">Pills</th><td><span class="answer-highlight"><?php echo $f_e1['pills']; ?></span></td></tr>
                <tr><th class="fixed-width">Blood Type</th><td><span class="answer-highlight"><?php echo $f_e1['blood_type']; ?></span></td></tr>
                <tr><th class="fixed-width">Conditions</th><td><span class="answer-highlight"><?php echo $f_e1['conditions_str']; ?></span></td></tr>
                </table>
                
                </div>
                </div>

                
                         <!-- end of new ui -->

                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>

        
            <!-- <div class="modal fade" id="historyModal<?php echo $f_e1['med_id']; ?>" tabindex="-1" aria-labelledby="historyModalLabel<?php echo $f_e1['med_id']; ?>" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="historyModalLabel<?php echo $f_e1['med_id']; ?>">Medical History Details</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                     
                       
                    </div>
                </div>
            </div> -->
                    <script>
                        <!-- Bootstrap CSS -->
                        <link href="https://maxcdn.bootstrapcdn.com/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
                        <!-- jQuery (optional, but recommended for Bootstrap 5 modal support) -->
                        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
                        <!-- Bootstrap JS -->
                        <script src="https://maxcdn.bootstrapcdn.com/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
                    </script>
            <!-- Edit Modal -->
            <div class="modal fade" id="editModal<?php echo $f_e1['med_id']; ?>" tabindex="-1" aria-labelledby="editModalLabel<?php echo $f_e1['med_id']; ?>" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="editModalLabel<?php echo $f_e1['med_id']; ?>">Edit Medical History</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <!-- Form starts here -->
                            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                                <input type="hidden" name="med_id" value="<?php echo $f_e1['med_id']; ?>" />
                                <div class="mb-3">
                                    <label for="med_prev_dent" class="form-label">Previous Dentist</label>
                                    <input type="text" class="form-control" id="med_prev_dent" name="med_prev_dent" value="<?php echo $f_e1['med_prev_dent']; ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="med_last_vi" class="form-label">Last Dental Visit</label>
                                    <input type="text" class="form-control" id="med_last_vi" name="med_last_vi" value="<?php echo $f_e1['med_last_vi']; ?>" required>
                                </div>
                               <div class="mb-3">
                                    <label for="med_date" class="form-label">Date</label>
                                    <input type="date" class="form-control" id="med_date" name="med_date" value="<?php echo $f_e1['med_date']; ?>" required>
                                </div>
                                <button type="submit" class="btn btn-success" name="update_medical_history">Submit</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            

            <?Php 
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_medical_history'])) {
    // Debug: Output the POST data
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";

    // Sanitize and collect the POST data
    $med_id = isset($_POST['med_id']) ? htmlspecialchars($_POST['med_id']) : '';
    $med_prev_dent = isset($_POST['med_prev_dent']) ? htmlspecialchars($_POST['med_prev_dent']) : '';
    $med_last_vi = isset($_POST['med_last_vi']) ? htmlspecialchars($_POST['med_last_vi']) : '';
    $med_date = isset($_POST['med_date']) ? htmlspecialchars($_POST['med_date']) : '';

    // Debug: Output sanitized variables
    echo "<pre>";
    echo "ID: $med_id\n";
    echo "Previous Dentist: $med_prev_dent\n";
    echo "Last Visit : $med_last_vi\n";
    echo "Date: $med_date\n";
    echo "</pre>";

    // Validate the data (basic validation)
    if (!empty($med_id) && !empty($med_prev_dent) && !empty($med_last_vi) && !empty($med_date) ) {
        
        // Prepare the SQL query to update the medical_history table
        $sql = "UPDATE medical_history SET med_prev_dent=?, med_last_vi=?, med_date=? WHERE med_id=?";

        // Use a prepared statement to avoid SQL injection
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("sssi", $med_prev_dent, $med_last_vi, $med_date, $med_id);

            // Execute the statement
            if ($stmt->execute()) {
                // Success: provide feedback to the user (or redirect)
              
                     echo '
                     <!-- Bootstrap CSS (Include this in the head section) -->
                     <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet" />
             
                     <!-- Modal for Login Failed Alert -->
                     <div class="modal fade" id="loginFailedModal" tabindex="-1" aria-labelledby="loginFailedLabel" aria-hidden="true">
                         <div class="modal-dialog">
                             <div class="modal-content">
                                 <div class="modal-header bg-success text-white">
                                     <h6 class="modal-title" id="loginFailedLabel">Medical History Updated</h6>
                                     <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                 </div>
                                 <div class="modal-body">
                                     Medical History Updated Successfully
                                 </div>
                               
                             </div>
                         </div>
                     </div>
             
                     <!-- JavaScript to Trigger the Modal and Redirect -->
                     <script>
                         document.addEventListener(\'DOMContentLoaded\', function () {
                             var modalEl = document.getElementById(\'loginFailedModal\');
                             var modal = new bootstrap.Modal(modalEl);
                             
                             // Show the modal when the page loads
                             modal.show();
             
                             // Redirect after the modal is closed
                             modalEl.addEventListener(\'hidden.bs.modal\', function () {
                                 window.location.href = \'http://localhost/vibrant_dental/medical-history.php\';
                             });
                         });
                     </script>
             
                     <!-- Bootstrap JS (Include this at the end of your HTML file) -->
                     <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
                     ';
            } else {
                // Failure: provide feedback to the user
                echo "<script>alert('Error updating record: " . $stmt->error . "');</script>";
            }
            $stmt->close();
        } else {
            echo "<script>alert('Error preparing statement: " . $conn->error . "');</script>";
        }

    } else {
        // Validation failed: provide feedback
        echo "<script>alert('All fields are required.');</script>";
    }
}
?>
<!-- logic for the modal -->
<!-- view modal -->
 
            <!-- delete Modal -->
            <div class="modal fade" id="deleteModal<?php echo $f_e1['med_id']; ?>" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
              <div class="modal-dialog">
                <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">Confirm Deletion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete this record?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST" class="d-inline">
                    <input type="hidden" name="med_id" value="<?php echo $f_e1['med_id']; ?>">
                    <button type="submit" name="delete_medical_history" class="btn btn-danger">Delete</button>
                    </form>
                </div>
                </div>
            </div>
            </div>
            <?php } ?>
            <?php 
					$q_e1 = $conn->query("SELECT * FROM `user_account1`");
					while($f_e1=$q_e1->fetch_array()){
                       
  ?>              
  <div class="modal fade" id="historyModal<?php echo $f_e1['med_id'];?>" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">History</h5>
        <button class="close" type="button" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">×</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="table-responsive">
          <div class="container mt-4">
            <?php
            $u_id_data = $f_e1['u_id'];
            $sql = "SELECT * FROM medical_history WHERE `u_id`='$u_id_data' ORDER BY med_id ASC";
            $result = mysqli_query($conn, $sql);
            
            if ($result) {
              while($row = mysqli_fetch_assoc($result)) {
                echo '<div class="card mb-3 shadow-sm">';
                echo '<div class="card-header bg-primary text-white"><strong>Medical History</strong></div>';
                echo '<div class="card-body">';
                
                // General Information
                echo '<h5 class="card-title">General Information</h5>';
                echo '<table class="table table-striped table-bordered">';
                echo '<tr><th class="fixed-width">Date</th><td>' . htmlspecialchars($row["med_date"]) . '</td></tr>';
                echo '<tr><th class="fixed-width">Previous Dentist</th><td>' . htmlspecialchars($row["med_prev_dent"]) . '</td></tr>';
                echo '<tr><th class="fixed-width">Last Visit</th><td>' . htmlspecialchars($row["med_last_vi"]) . '</td></tr>';
                echo '</table>';
                
                // Health Questions
                echo '<h5 class="card-title mt-4">Health Questions</h5>';
                echo '<table class="table table-striped table-bordered">';
                echo '<tr><th class="fixed-width">1.) Are you in good health?</th><td><span class="answer-highlight">' . htmlspecialchars($row["one"]) . '</span></td></tr>';
                echo '<tr><th class="fixed-width">2.) Are you under medical treatment now?</th><td><span class="answer-highlight">' . htmlspecialchars($row["two"]) . '</span></td></tr>';
                echo '<tr><th class="fixed-width">If so, what is the condition being treated?</th><td><span class="answer-highlight">' . htmlspecialchars($row["two_answer"]) . '</span></td></tr>';
                echo '<tr><th class="fixed-width">3.) Have you ever had a serious illness or surgical operation?</th><td><span class="answer-highlight">' . htmlspecialchars($row["three"]) . '</span></td></tr>';
                echo '<tr><th class="fixed-width">If so, what illness or operation?</th><td><span class="answer-highlight">' . htmlspecialchars($row["three_answer"]) . '</span></td></tr>';
                echo '<tr><th class="fixed-width">4.) Have you ever been hospitalized?</th><td><span class="answer-highlight">' . htmlspecialchars($row["four"]) . '</span></td></tr>';
                echo '<tr><th class="fixed-width">If so, when and why?</th><td><span class="answer-highlight">' . htmlspecialchars($row["four_answer"]) . '</span></td></tr>';
                echo '<tr><th class="fixed-width">5.) Are you taking any prescription/non-prescription medication?</th><td><span class="answer-highlight">' . htmlspecialchars($row["five"]) . '</span></td></tr>';
                echo '<tr><th class="fixed-width">If so, please specify:</th><td><span class="answer-highlight">' . htmlspecialchars($row["five_answer"]) . '</span></td></tr>';
                echo '<tr><th class="fixed-width">6.) Do you use tobacco products?</th><td><span class="answer-highlight">' . htmlspecialchars($row["six"]) . '</span></td></tr>';
                echo '<tr><th class="fixed-width">7.) Do you use alcohol, cocaine, or other dangerous drugs?</th><td><span class="answer-highlight">' . htmlspecialchars($row["seven"]) . '</span></td></tr>';
                echo '</table>';
                
                // Additional Information
                echo '<h5 class="card-title mt-4">Additional Information</h5>';
                echo '<table class="table table-striped table-bordered">';
                echo '<tr><th class="fixed-width">Allergies</th><td><span class="answer-highlight">' . htmlspecialchars($row["allergies_str"]) . '</span></td></tr>';
                echo '<tr><th class="fixed-width">Pregnant</th><td><span class="answer-highlight">' . htmlspecialchars($row["pregnant"]) . '</span></td></tr>';
                echo '<tr><th class="fixed-width">Nursing</th><td><span class="answer-highlight">' . htmlspecialchars($row["nursing"]) . '</span></td></tr>';
                echo '<tr><th class="fixed-width">Pills</th><td><span class="answer-highlight">' . htmlspecialchars($row["pills"]) . '</span></td></tr>';
                echo '<tr><th class="fixed-width">Blood Type</th><td><span class="answer-highlight">' . htmlspecialchars($row["blood_type"]) . '</span></td></tr>';
                echo '<tr><th class="fixed-width">Conditions</th><td><span class="answer-highlight">' . htmlspecialchars($row["conditions_str"]) . '</span></td></tr>';
                echo '</table>';
                
                echo '</div>'; // Close card-body
                echo '</div>'; // Close card
              }
            } else {
              echo '<div class="alert alert-warning" role="alert">No records found</div>';
            }
            ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php } ?>


        </div>
    </div>
</div>

<!-- logic for the modal -->
<?php
// Assuming database connection is already established elsewhere

// Handle form submission for deletion
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_medical_history'])) {
    // Debug: Output the POST data
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";

    // Sanitize and collect the POST data
    $id = isset($_POST['med_id']) ? htmlspecialchars($_POST['med_id']) : '';

    // Validate the data (basic validation)
    if (!empty($id)) {
        
        // Prepare the SQL query to delete from the medical_history table
        $sql = "DELETE FROM medical_history WHERE med_id=?";

        // Use a prepared statement to avoid SQL injection
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("i", $id);

            // Execute the statement
            if ($stmt->execute()) {
                // Success: provide feedback to the user (or redirect)
                echo "<script>alert('Record deleted successfully.'); window.location.href='medical-history.php';</script>";
            } else {
                // Failure: provide feedback to the user
                echo "<script>alert('Error deleting record: " . $stmt->error . "');</script>";
            }
            $stmt->close();
        } else {
            echo "<script>alert('Error preparing statement: " . $conn->error . "');</script>";
        }

    } else {
        // Validation failed: provide feedback
        echo "<script>alert('No record selected for deletion.');</script>";
    }
}
?>
<!-- logic for the modal -->




    <!-- Back to Top -->
    <a href="#" class="btn btn-lg btn-primary btn-lg-square rounded-circle back-to-top"><i class="bi bi-arrow-up"></i></a>


    <!-- JavaScript Libraries -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.1/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="lib/wow/wow.min.js"></script>
    <script src="lib/easing/easing.min.js"></script>
    <script src="lib/waypoints/waypoints.min.js"></script>
    <script src="lib/counterup/counterup.min.js"></script>
    <script src="lib/owlcarousel/owl.carousel.min.js"></script>

    <!-- Template Javascript -->
    <script src="js/main.js"></script>
</body>

</html>