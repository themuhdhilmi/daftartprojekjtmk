<?php

use App\Models\GlobalAdmin;
use App\Models\StaffMain;
use App\Models\StaffStudent;
use App\Models\StudentList;
use App\Models\StudentMain;
use App\Models\User;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/


Auth::routes();

Route::get('/', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

Route::get('admin_page/{id}', function ($id) {

    if (auth()->check() && auth()->user()->role == 'Admin')
    {

        $students = StudentMain::all();
        $studentsList = StudentList::all();
        $staffMain = StaffMain::all();
        $staffStudents = StaffStudent::all();
        $globalAdmin = GlobalAdmin::find(1);
        $StaffCanSupervise = StaffMain::where('can_supervise', '1')->count();

        $MainUser = User::all();

        if ($id == 'dashboard') {

            return view('Admin/dashboard',  [
                'globalAdmin' =>  $globalAdmin,
                'students' => $students ,
                'studentsList' => $studentsList,
                'staffMain' => $staffMain,
                'staffStudents' => $staffStudents,
                'StaffCanSupervise' => $StaffCanSupervise,
                'MainUser' => $MainUser
            ]);
        }

        if ($id == 'manage_admin') {

            return view('Admin/manage_admin',  [
                'globalAdmin' =>  $globalAdmin,
                'students' => $students ,
                'studentsList' => $studentsList,
                'staffMain' => $staffMain,
                'staffStudents' => $staffStudents,
                'StaffCanSupervise' => $StaffCanSupervise,
                'MainUser' => $MainUser
            ]);
        }

        if ($id == 'manage_staff') {

            return view('Admin/manage_staff',  [
                'globalAdmin' =>  $globalAdmin,
                'students' => $students ,
                'studentsList' => $studentsList,
                'staffMain' => $staffMain,
                'staffStudents' => $staffStudents,
                'StaffCanSupervise' => $StaffCanSupervise,
                'MainUser' => $MainUser
            ]);
        }

        if ($id == 'bulk_add_staff') {

            return view('Admin/bulk_add_staff',  [
                'globalAdmin' =>  $globalAdmin,
                'students' => $students ,
                'studentsList' => $studentsList,
                'staffMain' => $staffMain,
                'staffStudents' => $staffStudents,
                'StaffCanSupervise' => $StaffCanSupervise,
                'MainUser' => $MainUser
            ]);
        }

        if ($id == 'manage_student') {

            return view('Admin/manage_student',  [
                'globalAdmin' =>  $globalAdmin,
                'students' => $students,
                'studentsList' => $studentsList,
                'staffMain' => $staffMain,
                'staffStudents' => $staffStudents,
                'StaffCanSupervise' => $StaffCanSupervise,
                'MainUser' => $MainUser
            ]);
        }

        if ($id == 'global_value') {

            return view('Admin/global_value',  [
                'globalAdmin' =>  $globalAdmin,
                'students' => $students,
                'studentsList' => $studentsList,
                'staffMain' => $staffMain,
                'staffStudents' => $staffStudents,
                'StaffCanSupervise' => $StaffCanSupervise,
                'MainUser' => $MainUser
            ]);
        }


        if ($id == 'bulk_add_student') {

            return view('Admin/bulk_add_student',  [
                'globalAdmin' =>  $globalAdmin,
                'students' => $students,
                'studentsList' => $studentsList,
                'staffMain' => $staffMain,
                'staffStudents' => $staffStudents,
                'StaffCanSupervise' => $StaffCanSupervise,
                'MainUser' => $MainUser
            ]);
        }
    }

    abort(404);
})->name('admin_page');

Route::get('student_page/{id}', function ($id) {

    if (auth()->check() && auth()->user()->role == 'Student')
    {

        $students = StudentMain::all();
        $studentsList = StudentList::all();
        $staffMain = StaffMain::all();
        $staffStudents = StaffStudent::all();
        $globalAdmin = GlobalAdmin::find(1);
        $StaffCanSupervise = StaffMain::where('can_supervise', '1')->count();
        $MainUser = User::all();


        $currentStudent = StudentMain::where('email', Auth::user()->email)->first();
        $currentStudentGroupMember = StudentList::where('email', Auth::user()->email)->get();
        $currentStudentSupervisor = StaffStudent::where('email', Auth::user()->email)->first();
        $currentStudentSupervisorUser = User::where('email', $currentStudentSupervisor->email_staff)->first();
        if ($id == 'dashboard') {

            return view('Student/dashboard',  [
                'currentStudent' =>  $currentStudent,
                'currentStudentGroupMember' => $currentStudentGroupMember ,
                'currentStudentSupervisor' => $currentStudentSupervisor,
                'currentStudentSupervisorUser' => $currentStudentSupervisorUser
            ]);
        }

        if ($id == 'update_profile') {

            $supervisorThatCanSupervise = StaffMain::where('can_supervise', '1')->get();
            $supervisors[] = array();

            foreach ($supervisorThatCanSupervise as $supervisor)
            {
                $selected = User::where('email', $supervisor->email)->first();
                $quota = StaffStudent::where('email_staff', $supervisor->email)->where('is_confirmed', '1')->count();

                $supervisors[] =
                    [
                        'name' => $selected->name,
                        'email' => $supervisor->email,
                        'quota' =>  $quota
                    ];
            }

            return view('Student/update_profile',  [
                'currentStudent' =>  $currentStudent,
                'currentStudentGroupMember' => $currentStudentGroupMember ,
                'currentStudentSupervisor' => $currentStudentSupervisor,
                'currentStudentSupervisorUser' => $currentStudentSupervisorUser,
                'supervisors' => $supervisors,
                'globalAdmin' => $globalAdmin
            ]);
        }

        if ($id == 'change_password') {

            return view('Student/change_password',  [
                'currentStudent' =>  $currentStudent,
                'currentStudentGroupMember' => $currentStudentGroupMember ,
                'currentStudentSupervisor' => $currentStudentSupervisor,
                'currentStudentSupervisorUser' => $currentStudentSupervisorUser
            ]);
        }

        if ($id == 'supervisor_list') {

            return view('Student/supervisor_list',  [
                'currentStudent' =>  $currentStudent,
                'currentStudentGroupMember' => $currentStudentGroupMember ,
                'currentStudentSupervisor' => $currentStudentSupervisor,
                'currentStudentSupervisorUser' => $currentStudentSupervisorUser
            ]);
        }

    }

    abort(404);
})->name('student_page');


