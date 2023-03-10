<?php

namespace App\Http\Controllers;
use App\Models\GlobalAdmin;
use App\Models\StaffMain;
use App\Models\StaffStudent;
use Auth;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Request;
use Validator;

class StaffController extends Controller
{

    public function createStaff(Request $request)
    {
        // Validate the request data
        $request->validate([
            'email' => 'required|string|email|unique:users',
            'password' => 'required|string|min:8',
            'track' => 'required|string',
            'scopus_id' => 'nullable|string',
            'google_scholar' => 'nullable|string',
            'consultation_price' => 'nullable|integer',
            'send_email_notification' => 'nullable|boolean',
        ]);

        // Create the new user
        $user = new User();
        $user->name = $request->input('full_name');
        $user->email = $request->input('email');
        $user->password = Hash::make($request->input('password'));
        $user->role = 'Staff';
        $user->save();

        // Create the new StaffMain model
        $staff = new StaffMain();
        $staff->email = $user->email;
        $staff->track = $request->input('track');
        $staff->scopus_id = $request->input('scopus_id');
        $staff->google_scholar = $request->input('google_scholar');
        $staff->consultation_price = $request->input('consultation_price');
        $staff->send_email_notification = $request->input('send_email_notification');
        $staff->save();

        return response()->json($user, 201);
    }

    public function updateStaff(Request $request)
    {

        $resultData = [];

        // Iterate over the models and add the data to the array
        foreach (StaffMain::all() as $model) {

            $resultData[] = [
                'current_email' => $model->email,
                'form_input_name' => $request->input(str_replace(".", "_", 'staff_name_' . $model->email)),
                'form_input_email' => $request->input(str_replace(".", "_", 'staff_email_' . $model->email)),
                'form_input_track' => $request->input(str_replace(".", "_", 'staff_track_' . $model->email)),
                'form_input_supervisor_status' => $request->input(str_replace(".", "_", 'staff_supervisor_' . $model->email)),
            ];

            //array_push($resultData,  $data);
        }

        foreach ($resultData as $data) {
            // Find the user by email
            $user = User::where('email', $data['current_email'])->first();

            $trimmedEmail = trim($data['form_input_email']);

            if ($user) {
                // Update the user name
                $user->name = $data['form_input_name'];
                $user->email = $trimmedEmail;
                $user->save();

                // Find the StaffMain model by email
                $staff = StaffMain::where('email', $data['current_email'])->first();
                if ($staff) {
                    //Update the StaffMain model
                    $staff->email = $trimmedEmail;
                    $staff->track = $data['form_input_track'];
                    $staff->can_supervise = $data['form_input_supervisor_status'];
                    $staff->save();
                }

                if($data['form_input_supervisor_status'] == '0')
                {
                    StaffStudent::where('email_staff', $data['current_email'])->update([
                        'email_staff' => '',
                        'is_confirmed' => 0
                    ]);
                }
                else
                {
                    // StaffStudent::where('email_staff', $data['current_email'])->update([
                    //     'email_staff' => $trimmedEmail,
                    //     'is_confirmed' => 0
                    // ]);
                }

            }
        }

        //return response()->json([$request->all()], 200);
        //return response()->json([$resultData, ], 200);
        return redirect()->route('admin_page', ['id' => 'manage_staff', 'message' => 'success']);
    }

    public function bulkCreateStaff(Request $request)
    {
        //Validate the request data
        $request->validate([
            'textBulkInsertStaff' => 'required|string',
        ]);


        //return response()->json($request->all());

        // Split the CSV string into an array of lines
        $csv_lines = explode("\r\n", $request->input('textBulkInsertStaff'));


        $sucess = [];
        $error = [];
        $index = 0;
        // Iterate over the lines and create new users for each one
        foreach ($csv_lines as $line) {
            $index++;
            // Split the line into an array of values
            $values = explode(",", $line);

            $error_res = [];
            // Validate the values
            if (count($values) != 4) {
                array_push($error_res, 'error : not complete');
                $the_res = [$index => $error_res];
                array_push($error, $the_res);
                continue;
                //return response()->json(['message' => 'error : not complete']);
            }
            // Email
            if (!filter_var($values[0], FILTER_VALIDATE_EMAIL)) {
                array_push($error_res, 'error : invalid email');
                //return response()->json(['message' => 'error : invalid email' . $values[1]]);
            }
            $user = User::where('email', $values[0])->first();
            if ($user) {
                array_push($error_res, 'User already exists');
                //return response()->json(['message' => 'User already exists'], 422);
            }
            // Pasword
            if (strlen($values[3]) < 8) {
                array_push($error_res, 'error : password less than 8');
                //return response()->json(['message' => 'error : password less than 8']);
            }
            // name
            if (strlen($values[1]) <= 5) {
                array_push($error_res, 'error : invalid name');
                //return response()->json(['message' => 'error : invalid name']);
            }
            // track
            if (!in_array($values[2], ['programming', 'networking', 'security'])) {
                array_push($error_res, 'error : invalid track');
                //return response()->json(['message' => 'error : invalid track']);
            }


            if (!empty($error_res)) {
                $the_res = [$index => $error_res];
                array_push($error, $the_res);
                continue;
            }

            // Create the new user
            $user = new User();
            $user->name = $values[1];
            $user->email = $values[0];
            $user->password = Hash::make($values[3]);
            $user->role = 'Staff';
            $user->save();

            // Create the new StaffMain model
            $staff = new StaffMain();
            $staff->email = $values[0];
            $staff->track = $values[2];
            $staff->save();

            $the_res = [$index => ['Successfully inserted']];
            array_push($sucess, $the_res);

        }

        return response()->json(['succes' => $sucess, 'error' => $error], 201);
    }

    public function changeStaffPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'txtCurrentPassword' => 'required|string',
            'txtNewPassword' => 'required|string|min:8|confirmed',
            'txtEmail' => 'required|email',
        ]);

        if ($validator->fails()) {
            //return response()->json(['errors' => $validator->errors()], 400);

            $stringErrorCollection = '';

            $errors = $validator->errors();
            foreach ($errors->all() as $error) {
                $stringErrorCollection = $stringErrorCollection . $error;
            }


            return redirect()->route('staff_page', ['id' => 'change_password', 'errors' => $stringErrorCollection]);
        }


        $user = User::where('email', $request->input('txtEmail'))->first();


        if (!Hash::check($request->txtCurrentPassword, $user->password)) {
            //return response()->json(['errors' => 'The provided old password is incorrect.'], 400);

            return redirect()->route('staff_page', ['id' => 'change_password', 'errors' => 'Old password not match.']);
        }

        // Update the user's password
        $user->password = Hash::make($request->txtNewPassword);
        $user->save();

        return redirect()->route('staff_page', ['id' => 'change_password','success' => 'Password successfully changed.']);

    }

    public function supervisorRequest(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'txtEmail' => 'required|string',
            'txtStudentEmail' => 'required|string',
            'buttonSelected' => 'required|string',
        ]);

        if($request->input('buttonSelected') == 'approve')
        {
            $currentSuperviseeRequest = StaffStudent::where('email_staff', $request->input('txtEmail'))->where('is_confirmed', '0')->get();
            $currentSuperviseeCount = StaffStudent::where('email_staff', $request->input('txtEmail'))->where('is_confirmed', '1')->count();
            $globalAdmin = GlobalAdmin::all()->first();

            if(intval($currentSuperviseeCount) >= intval($globalAdmin->quota))
            {
                //If quota exceeded, remove all request.
                foreach ($currentSuperviseeRequest as $user) {

                    StaffStudent::where('email', $user->email)->update([
                        'email_staff' => '',
                        'is_confirmed' => 0
                    ]);

                }
                return redirect()->route('staff_page', ['id' => 'supervisor_request', 'errors' => 'Quota limit reached, declined all request']);
                //return response()->json(['errors' => 'Quota limit reached, declined all request']);
            }

            StaffStudent::where('email', $request->input('txtStudentEmail'))->update([
                'is_confirmed' => 1
            ]);

            $currentSuperviseeRequest = StaffStudent::where('email_staff', $request->input('txtEmail'))->where('is_confirmed', '0')->get();
            $currentSuperviseeCount = StaffStudent::where('email_staff', $request->input('txtEmail'))->where('is_confirmed', '1')->count();
            $globalAdmin = GlobalAdmin::all()->first();

            if(intval($currentSuperviseeCount) >= intval($globalAdmin->quota))
            {
                //If quota exceeded, remove all request.
                foreach ($currentSuperviseeRequest as $user) {

                    StaffStudent::where('email', $user->email)->update([
                        'email_staff' => '',
                        'is_confirmed' => 0
                    ]);

                }
                return redirect()->route('staff_page', ['id' => 'supervisor_request', 'success' => 'Success! Students accepted. Limit reached, decline the rest of students']);
                //return response()->json(['success' => 'Success! Students accepted. Limit reached, decline the rest of students']);
            }

            return redirect()->route('staff_page', ['id' => 'supervisor_request','success' => 'Success! Students accepted.']);
            //return response()->json(['success' => 'Success! Students accepted.']);
        }

        if($request->input('buttonSelected') == 'decline')
        {
            $currentSuperviseeRequest = StaffStudent::where('email_staff', $request->input('txtEmail'))->where('is_confirmed', '0')->get();
            $currentSuperviseeCount = StaffStudent::where('email_staff', $request->input('txtEmail'))->where('is_confirmed', '1')->count();
            $globalAdmin = GlobalAdmin::all()->first();

            if(intval($currentSuperviseeCount) >= intval($globalAdmin->quota))
            {
                //If quota exceeded, remove all request.
                foreach ($currentSuperviseeRequest as $user) {

                    StaffStudent::where('email', $user->email)->update([
                        'email_staff' => '',
                        'is_confirmed' => 0
                    ]);

                }
                return redirect()->route('staff_page', ['id' => 'supervisor_request','success' => 'Quota limit reached, all request automatically declined.']);
                //return response()->json(['success' => 'Quota limit reached, declined all request']);
            }

            StaffStudent::where('email', $request->input('txtStudentEmail'))->update([
                'email_staff' => '',
                'is_confirmed' => 0
            ]);

            return redirect()->route('staff_page', ['id' => 'supervisor_request','success' => 'Success! Students declined.']);
            //return response()->json(['success' => 'Success! Students declined.']);
        }


        return redirect()->route('staff_page', ['id' => 'supervisor_request', 'errors' => 'Something gone wrong!']);
        //return response()->json(['errors' => 'Something gone wrong!']);
    }


    public function superviseeDelete(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'txtEmail' => 'required|string',
            'txtStudentEmail' => 'required|string',
        ]);

            StaffStudent::where('email', $request->input('txtStudentEmail'))->update([
                'email_staff' => '',
                'is_confirmed' => 0
            ]);

            return redirect()->route('staff_page', ['id' => 'manage_supervisee','success' => 'Success! Students declined.']);
            //return response()->json(['success' => 'Success! Students declined.']);
    }

    public function updateProfilePicture(Request  $request)
    {
        request()->validate([
            'fileImage' => 'required|mimes:jpg|max:5000',
            'txtEmail' => 'required|string',
        ]);

        try {
            $request->file('fileImage')->move(public_path().'/downloadable/staff_img',  Auth::user()->email . '.jpg');
        } catch (\Exception $e) {
            return redirect()->route('staff_page', ['id' => 'update_profile','errors' => 'Error: File upload failed.', 'profile_picture' => 'profile_picture']);
        }

        return redirect()->route('staff_page', ['id' => 'update_profile','success' => 'Success! Profile picture uploaded.', 'profile_picture' => 'profile_picture']);
    }

    public function updateAdditionInformation(Request $request)
    {
        request()->validate([
            'txtScopusId' => 'nullable|string',
            'txtGoogleScholar' => 'nullable|string',
            'txtConsultationPrice' => 'nullable|numeric'
        ]);

        $staff = StaffMain::where('email', Auth::user()->email)->first();

        if($staff) {
            $staff->scopus_id = $request->input('txtScopusId');
            $staff->google_scholar = $request->input('txtGoogleScholar');
            $staff->consultation_price = $request->input('txtConsultationPrice');
            $staff->save();
            return redirect()->route('staff_page', ['id' => 'update_profile','success' => 'Success! Staff information updated.' ,  'addition_information' => 'addition_information']);
        }
        else{
            return redirect()->route('staff_page', ['id' => 'update_profile','errors' => 'Error: Staff not found.',  'addition_information' => 'addition_information']);
        }
    }



}
