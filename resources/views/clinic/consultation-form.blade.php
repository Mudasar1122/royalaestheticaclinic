@extends('layout.layout')

@php
    $title='Consultation Form';
    $subTitle = 'Royal Aesthetica';
@endphp

@section('content')

    <form action="#" class="grid grid-cols-12 gap-6">
        <div class="col-span-12">
            <div class="card border-0">
                <div class="card-header">
                    <h6 class="text-lg font-semibold mb-0">Clinic & Appointment Details</h6>
                </div>
                <div class="card-body">
                    <div class="grid grid-cols-12 gap-4">
                        <div class="col-span-12 lg:col-span-4">
                            <label class="form-label">Clinic Name</label>
                            <input type="text" class="form-control" value="Royal Aesthetica, Faisalabad" readonly>
                        </div>
                        <div class="col-span-12 lg:col-span-4">
                            <label class="form-label">Clinic Contact 1</label>
                            <input type="text" class="form-control" value="0303-7633000" readonly>
                        </div>
                        <div class="col-span-12 lg:col-span-4">
                            <label class="form-label">Clinic Contact 2</label>
                            <input type="text" class="form-control" value="(041) 8726300" readonly>
                        </div>
                        <div class="col-span-12 lg:col-span-4">
                            <label class="form-label">Consultant</label>
                            <input type="text" class="form-control" placeholder="Consultant name">
                        </div>
                        <div class="col-span-12 lg:col-span-4">
                            <label class="form-label">Date</label>
                            <input type="date" class="form-control">
                        </div>
                        <div class="col-span-12 lg:col-span-4">
                            <label class="form-label">Time</label>
                            <input type="time" class="form-control">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12">
            <div class="card border-0">
                <div class="card-header">
                    <h6 class="text-lg font-semibold mb-0">Patient Information</h6>
                </div>
                <div class="card-body">
                    <div class="grid grid-cols-12 gap-4">
                        <div class="col-span-12 lg:col-span-4">
                            <label class="form-label">Patient Name</label>
                            <input type="text" class="form-control" placeholder="Full name">
                        </div>
                        <div class="col-span-12 lg:col-span-4">
                            <label class="form-label">Phone No.</label>
                            <input type="text" class="form-control" placeholder="+92">
                        </div>
                        <div class="col-span-12 lg:col-span-4">
                            <label class="form-label">Age</label>
                            <input type="number" class="form-control" placeholder="Years">
                        </div>
                        <div class="col-span-12 lg:col-span-4">
                            <label class="form-label">Date of Birth</label>
                            <input type="date" class="form-control">
                        </div>
                        <div class="col-span-12 lg:col-span-4">
                            <label class="form-label">Height (cm)</label>
                            <input type="number" class="form-control" placeholder="cm">
                        </div>
                        <div class="col-span-12 lg:col-span-4">
                            <label class="form-label">Weight (kg)</label>
                            <input type="number" class="form-control" placeholder="kg">
                        </div>
                        <div class="col-span-12 lg:col-span-4">
                            <label class="form-label">BMI</label>
                            <input type="text" class="form-control" placeholder="BMI">
                        </div>
                        <div class="col-span-12 lg:col-span-4">
                            <label class="form-label">Gender</label>
                            <select class="form-select">
                                <option value="">Select</option>
                                <option>Male</option>
                                <option>Female</option>
                                <option>Other</option>
                            </select>
                        </div>
                        <div class="col-span-12 lg:col-span-4">
                            <label class="form-label">How Did You Hear About Us?</label>
                            <select class="form-select">
                                <option value="">Select</option>
                                <option>Family / Friend</option>
                                <option>Facebook</option>
                                <option>Instagram</option>
                                <option>Google</option>
                                <option>Walk-in</option>
                                <option>Other</option>
                            </select>
                        </div>
                        <div class="col-span-12">
                            <label class="form-label">Address</label>
                            <input type="text" class="form-control" placeholder="Address">
                        </div>
                        <div class="col-span-12">
                            <label class="form-label">Patient's Main Concern</label>
                            <textarea class="form-control" rows="3" placeholder="Explain your main concern or goal"></textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12">
            <div class="card border-0">
                <div class="card-header">
                    <h6 class="text-lg font-semibold mb-0">Consultation Summary</h6>
                </div>
                <div class="card-body">
                    <div class="grid grid-cols-12 gap-4">
                        <div class="col-span-12 lg:col-span-6">
                            <label class="form-label">Procedures of Interest</label>
                            <select class="form-select" multiple>
                                <option>Laser Hair Removal</option>
                                <option>Acne / Acne Scars</option>
                                <option>Pigmentation / Melasma / Freckles</option>
                                <option>Anti-Aging / Face Lifting</option>
                                <option>Botox / Dermal Fillers</option>
                                <option>PRP (Face / Hair)</option>
                                <option>Hair Restoration / Hair Fall Treatment</option>
                                <option>Skin Tightening (HIFU / RF)</option>
                                <option>Chemical Peels / Carbon Peel</option>
                                <option>Body Contouring / Fat Reduction</option>
                                <option>Stretch Marks</option>
                                <option>Keloid / Hypertrophic Scars</option>
                                <option>Skin Whitening / Brightening</option>
                                <option>Cosmetic / Surgical Consultation</option>
                                <option>Other</option>
                            </select>
                            <p class="text-xs text-secondary-light mt-2 mb-0">Hold Ctrl/Cmd to select multiple.</p>
                        </div>
                        <div class="col-span-12 lg:col-span-6">
                            <label class="form-label">Consultation Type</label>
                            <select class="form-select" multiple>
                                <option>Skin Consultation</option>
                                <option>Laser Consultation</option>
                                <option>Hair Consultation</option>
                                <option>Weight Loss / Body Shaping</option>
                                <option>General Aesthetic Consultation</option>
                            </select>
                        </div>
                        <div class="col-span-12 lg:col-span-6">
                            <label class="form-label">Previous Treatments</label>
                            <select class="form-select" multiple>
                                <option>Botox (Wrinkles / Hyperhidrosis)</option>
                                <option>Dermal Fillers</option>
                                <option>Chemical Peels</option>
                                <option>Laser / IPL</option>
                                <option>PRP / Microneedling</option>
                                <option>Body Contouring</option>
                                <option>Other</option>
                            </select>
                        </div>
                        <div class="col-span-12 lg:col-span-6">
                            <label class="form-label">Approx. Date / Outcome</label>
                            <input type="text" class="form-control" placeholder="Date and outcome notes">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12">
            <div class="card border-0">
                <div class="card-header">
                    <h6 class="text-lg font-semibold mb-0">Medical History</h6>
                </div>
                <div class="card-body">
                    <div class="grid grid-cols-12 gap-4">
                        <div class="col-span-12">
                            <label class="form-label">Current Medical Conditions</label>
                            <input type="text" class="form-control" placeholder="Details">
                        </div>
                        <div class="col-span-12 lg:col-span-6">
                            <label class="form-label">Past Serious Illnesses</label>
                            <input type="text" class="form-control" placeholder="Details">
                        </div>
                        <div class="col-span-12 lg:col-span-6">
                            <label class="form-label">Surgeries / Hospitalizations</label>
                            <input type="text" class="form-control" placeholder="Details">
                        </div>
                        <div class="col-span-12 lg:col-span-6">
                            <label class="form-label">Medical Conditions</label>
                            <select class="form-select" multiple>
                                <option>Diabetes</option>
                                <option>Thyroid Disorder</option>
                                <option>Hypertension</option>
                                <option>Heart Disease</option>
                                <option>Bleeding Disorder</option>
                                <option>Epilepsy / Seizures</option>
                                <option>Asthma</option>
                                <option>Autoimmune Disease</option>
                            </select>
                        </div>
                        <div class="col-span-12 lg:col-span-6">
                            <label class="form-label">Skin Conditions</label>
                            <select class="form-select" multiple>
                                <option>Eczema</option>
                                <option>Psoriasis</option>
                                <option>Vitiligo</option>
                                <option>Active Infection</option>
                            </select>
                        </div>
                        <div class="col-span-12 lg:col-span-6">
                            <label class="form-label">History of Cold Sores (Herpes)</label>
                            <input type="text" class="form-control" placeholder="Last episode">
                        </div>
                        <div class="col-span-12 lg:col-span-6">
                            <label class="form-label">Tendency to Form Keloids</label>
                            <select class="form-select">
                                <option value="">Select</option>
                                <option>Yes</option>
                                <option>No</option>
                            </select>
                        </div>
                        <div class="col-span-12">
                            <label class="form-label">Psychological Concerns</label>
                            <select class="form-select" multiple>
                                <option>Anxiety</option>
                                <option>Depression</option>
                                <option>Fear of Needles</option>
                                <option>Body Dysmorphic Concerns</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12">
            <div class="card border-0">
                <div class="card-header">
                    <h6 class="text-lg font-semibold mb-0">Medications & Allergies</h6>
                </div>
                <div class="card-body">
                    <div class="grid grid-cols-12 gap-4">
                        <div class="col-span-12">
                            <label class="form-label">Current Medications / Supplements</label>
                            <input type="text" class="form-control" placeholder="List medications">
                        </div>
                        <div class="col-span-12 lg:col-span-6">
                            <label class="form-label">Recent or Current Use</label>
                            <select class="form-select" multiple>
                                <option>Steroids</option>
                                <option>Retinol / Vitamin A</option>
                                <option>Roaccutane (Isotretinoin)</option>
                            </select>
                        </div>
                        <div class="col-span-12 lg:col-span-6">
                            <label class="form-label">If Roaccutane, date stopped</label>
                            <input type="text" class="form-control" placeholder="Date">
                        </div>
                        <div class="col-span-12 lg:col-span-6">
                            <label class="form-label">Allergies</label>
                            <select class="form-select" multiple>
                                <option>Drug</option>
                                <option>Latex</option>
                                <option>Plaster</option>
                                <option>Iodine</option>
                                <option>None</option>
                            </select>
                        </div>
                        <div class="col-span-12 lg:col-span-6">
                            <label class="form-label">Allergy to Penicillin</label>
                            <select class="form-select">
                                <option value="">Select</option>
                                <option>Yes</option>
                                <option>No</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12">
            <div class="card border-0">
                <div class="card-header">
                    <h6 class="text-lg font-semibold mb-0">Lifestyle & Female Health (If Applicable)</h6>
                </div>
                <div class="card-body">
                    <div class="grid grid-cols-12 gap-4">
                        <div class="col-span-12 lg:col-span-4">
                            <label class="form-label">Smoking</label>
                            <select class="form-select">
                                <option value="">Select</option>
                                <option>Yes</option>
                                <option>No</option>
                            </select>
                        </div>
                        <div class="col-span-12 lg:col-span-4">
                            <label class="form-label">Alcohol Intake</label>
                            <select class="form-select">
                                <option value="">Select</option>
                                <option>Yes</option>
                                <option>No</option>
                            </select>
                        </div>
                        <div class="col-span-12 lg:col-span-4">
                            <label class="form-label">Pregnant or Breastfeeding</label>
                            <select class="form-select">
                                <option value="">Select</option>
                                <option>Yes</option>
                                <option>No</option>
                                <option>Not Applicable</option>
                            </select>
                        </div>
                        <div class="col-span-12 lg:col-span-4">
                            <label class="form-label">History of HIV / Hepatitis Exposure</label>
                            <select class="form-select">
                                <option value="">Select</option>
                                <option>Yes</option>
                                <option>No</option>
                            </select>
                        </div>
                        <div class="col-span-12 lg:col-span-4">
                            <label class="form-label">Sleep Pattern</label>
                            <select class="form-select">
                                <option value="">Select</option>
                                <option>Good</option>
                                <option>Moderate</option>
                                <option>Poor</option>
                            </select>
                        </div>
                        <div class="col-span-12 lg:col-span-4">
                            <label class="form-label">Diet Habits</label>
                            <select class="form-select">
                                <option value="">Select</option>
                                <option>Healthy</option>
                                <option>Average</option>
                                <option>Unhealthy</option>
                            </select>
                        </div>
                        <div class="col-span-12 lg:col-span-4">
                            <label class="form-label">Daily Water Intake (Liters)</label>
                            <input type="number" class="form-control" placeholder="Liters">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12">
            <div class="card border-0">
                <div class="card-header">
                    <h6 class="text-lg font-semibold mb-0">Clinical Assessment (Staff Use)</h6>
                </div>
                <div class="card-body">
                    <div class="grid grid-cols-12 gap-4">
                        <div class="col-span-12 lg:col-span-4">
                            <label class="form-label">Vitals</label>
                            <input type="text" class="form-control" placeholder="BP / Pulse / Temp">
                        </div>
                        <div class="col-span-12 lg:col-span-4">
                            <label class="form-label">Body Metrics</label>
                            <input type="text" class="form-control" placeholder="Height / Weight / BMI / IBW">
                        </div>
                        <div class="col-span-12 lg:col-span-4">
                            <label class="form-label">Skin Type</label>
                            <select class="form-select" multiple>
                                <option>Dry</option>
                                <option>Oily</option>
                                <option>Combination</option>
                                <option>Sensitive</option>
                            </select>
                        </div>
                        <div class="col-span-12 lg:col-span-4">
                            <label class="form-label">Fitzpatrick Skin Type</label>
                            <select class="form-select">
                                <option>I</option>
                                <option>II</option>
                                <option>III</option>
                                <option>IV</option>
                                <option>V</option>
                                <option>VI</option>
                            </select>
                        </div>
                        <div class="col-span-12 lg:col-span-8">
                            <label class="form-label">Skin Analysis / Scan</label>
                            <select class="form-select" multiple>
                                <option>Sebum</option>
                                <option>Moisture</option>
                                <option>Pigmentation</option>
                                <option>Collagen</option>
                                <option>Elasticity</option>
                            </select>
                        </div>
                        <div class="col-span-12 lg:col-span-4">
                            <label class="form-label">Photographic Records</label>
                            <select class="form-select">
                                <option value="">Select</option>
                                <option>Taken</option>
                                <option>Not Taken</option>
                            </select>
                        </div>
                        <div class="col-span-12">
                            <label class="form-label">Clinical Findings / Notes</label>
                            <textarea class="form-control" rows="3" placeholder="Notes"></textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12">
            <div class="card border-0">
                <div class="card-header">
                    <h6 class="text-lg font-semibold mb-0">Treatment Recommendation</h6>
                </div>
                <div class="card-body">
                    <div class="grid grid-cols-12 gap-4">
                        <div class="col-span-12 lg:col-span-6">
                            <label class="form-label">Treatment</label>
                            <select class="form-select">
                                <option value="">Select</option>
                                <option>HydraFacial</option>
                                <option>Laser</option>
                                <option>PRP</option>
                                <option>Dermal Fillers</option>
                                <option>Fat Dissolving</option>
                                <option>Cryolipolysis</option>
                            </select>
                        </div>
                        <div class="col-span-12 lg:col-span-3">
                            <label class="form-label">Sessions</label>
                            <input type="number" class="form-control" placeholder="No.">
                        </div>
                        <div class="col-span-12 lg:col-span-3">
                            <label class="form-label">Retail Price</label>
                            <input type="text" class="form-control" placeholder="Price">
                        </div>
                        <div class="col-span-12 lg:col-span-3">
                            <label class="form-label">Discount %</label>
                            <input type="number" class="form-control" placeholder="%">
                        </div>
                        <div class="col-span-12 lg:col-span-3">
                            <label class="form-label">Offered Price</label>
                            <input type="text" class="form-control" placeholder="Price">
                        </div>
                        <div class="col-span-12 lg:col-span-3">
                            <label class="form-label">Willing to Pay</label>
                            <select class="form-select">
                                <option value="">Select</option>
                                <option>Yes</option>
                                <option>No</option>
                            </select>
                        </div>
                        <div class="col-span-12 lg:col-span-3">
                            <label class="form-label">Client Converted</label>
                            <select class="form-select">
                                <option value="">Select</option>
                                <option>Yes</option>
                                <option>No</option>
                            </select>
                        </div>
                        <div class="col-span-12">
                            <label class="form-label">Additional Notes / Remarks</label>
                            <textarea class="form-control" rows="3" placeholder="Notes"></textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-span-12">
            <div class="card border-0">
                <div class="card-header">
                    <h6 class="text-lg font-semibold mb-0">Patient Consent & Declaration</h6>
                </div>
                <div class="card-body">
                    <p class="text-sm text-secondary-light mb-4">
                        I confirm that the information provided above is true and complete to the best of my knowledge.
                        I understand the nature of the proposed procedures, possible risks, benefits, alternatives, and expected outcomes.
                        I agree to follow pre- and post-treatment instructions and inform the clinic of any changes in my medical condition.
                    </p>
                    <div class="grid grid-cols-12 gap-4">
                        <div class="col-span-12 lg:col-span-6">
                            <label class="form-label">Consent for Clinical Photography</label>
                            <select class="form-select">
                                <option value="">Select</option>
                                <option>Yes</option>
                                <option>No</option>
                            </select>
                        </div>
                        <div class="col-span-12 lg:col-span-6">
                            <label class="form-label">Consent for Educational / Marketing Use</label>
                            <select class="form-select">
                                <option value="">Select</option>
                                <option>Yes</option>
                                <option>No</option>
                            </select>
                        </div>
                        <div class="col-span-12 lg:col-span-6">
                            <label class="form-label">Patient Name</label>
                            <input type="text" class="form-control" placeholder="Name">
                        </div>
                        <div class="col-span-12 lg:col-span-6">
                            <label class="form-label">Patient Signature</label>
                            <input type="text" class="form-control" placeholder="Signature">
                        </div>
                        <div class="col-span-12 lg:col-span-6">
                            <label class="form-label">Date</label>
                            <input type="date" class="form-control">
                        </div>
                        <div class="col-span-12 lg:col-span-6">
                            <label class="form-label">Consultant / Aesthetic Physician Name</label>
                            <input type="text" class="form-control" placeholder="Name">
                        </div>
                        <div class="col-span-12 lg:col-span-6">
                            <label class="form-label">Consultant Signature</label>
                            <input type="text" class="form-control" placeholder="Signature">
                        </div>
                        <div class="col-span-12 lg:col-span-6">
                            <label class="form-label">Date</label>
                            <input type="date" class="form-control">
                        </div>
                    </div>
                    <p class="text-xs text-secondary-light mt-4 mb-0">
                        This form is confidential and intended solely for clinical use at Royal Aesthetica.
                    </p>
                </div>
            </div>
        </div>
    </form>

@endsection
