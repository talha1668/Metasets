<?php

namespace App\Http\Controllers\User;

use App\Concerns\UploadedFile;
use App\Http\Controllers\Controller;
use App\Models\KycVerification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Exception;

class SettingsController extends Controller
{
    use UploadedFile;

    /**
     * @return Response
     */
    public function index(): Response
    {
        $user = auth()->user()->load(['wallet', 'kycVerification']);

        return Inertia::render('User/Settings/Index', [
            'user' => [
                'id' => $user->id,
                'uid' => $user->uid,
                'name' => $user->name,
                'email' => $user->email,
                'gender' => $user->gender,
                'avatar' => $user->avatar,
                'avatar_url' => $user->avatar_url,
                'referral_code' => $user->referral_code,
                'kyc_status' => $user->kycVerification?->status ?? 'pending',
                'kyc_verification' => $user->kycVerification ? [
                    'first_name' => $user->kycVerification->first_name,
                    'last_name' => $user->kycVerification->last_name,
                    'date_of_birth' => $user->kycVerification->date_of_birth,
                    'phone' => $user->kycVerification->phone,
                    'address' => $user->kycVerification->address,
                    'city' => $user->kycVerification->city,
                    'state' => $user->kycVerification->state,
                    'country' => $user->kycVerification->country,
                    'postal_code' => $user->kycVerification->postal_code,
                    'document_type' => $user->kycVerification->document_type,
                    'status' => $user->kycVerification->status,
                    'submitted_at' => $user->kycVerification->submitted_at,
                    'rejection_reason' => $user->kycVerification->rejection_reason,
                ] : null,
                'created_at' => $user->created_at,
                'wallet' => $user->wallet,
            ],
        ]);
    }

    /**
     * @param Request $request
     * @return RedirectResponse
     */
    public function updateProfile(Request $request): RedirectResponse
    {
        try {
            $user = auth()->user();
            $validator = Validator::make($request->all(), [
                'name' => ['required', 'string', 'max:255', 'min:2', 'regex:/^[a-zA-Z\s]+$/'],
                'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
                'gender' => 'nullable|in:male,female,other',
                'avatar' => 'nullable|image|max:2048|mimes:jpg,jpeg,png',
            ], [
                'name.regex' => 'Name can only contain letters and spaces.',
                'email.email' => 'Please provide a valid email address.',
                'avatar.max' => 'Avatar size must not exceed 2MB.',
            ]);

            if ($validator->fails()) {
                return back()->withErrors($validator)->withInput();
            }

            DB::beginTransaction();

            $data = [
                'name' => trim($request->name),
                'email' => strtolower(trim($request->email)),
                'gender' => $request->gender,
            ];

            if ($request->hasFile('avatar')) {
                $avatarFile = $request->file('avatar');

                if (!$avatarFile->isValid()) {
                    DB::rollBack();
                    return back()->with('error', 'The uploaded avatar file is corrupted. Please try again.');
                }

                if ($user->avatar) {
                    $this->removeFile($user->avatar);
                }

                $avatarPath = $this->move($avatarFile);
                $data['avatar'] = $avatarPath;

                Log::info('Avatar uploaded successfully', [
                    'user_id' => $user->id,
                    'avatar_path' => $avatarPath
                ]);
            }

            $user->update($data);
            DB::commit();
            return back()->with('success', 'Profile updated successfully.');

        } catch (Exception $e) {
            DB::rollBack();

            Log::error('Profile update error: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()->with('error', 'Failed to update profile. Please try again.');
        }
    }


    public function updatePassword(Request $request): RedirectResponse
    {
        $user = Auth::user();

        try {
            $validatedData = $request->validate([
                'current_password' => [
                    'required',
                    'string',
                    'min:6',
                    'max:255'
                ],
                'password' => [
                    'required',
                    'confirmed',
                    'max:255',
                    Password::min(8)
                ],
            ], [
                'password.uncompromised' => 'This password has appeared in a data breach. Please choose a different password.',
            ]);

            $currentPassword = $validatedData['current_password'];
            $newPassword = $validatedData['password'];

            if (!Hash::check($currentPassword, $user->password)) {
                Log::warning('Password change failed - incorrect current password', [
                    'user_id' => $user->id,
                    'ip_address' => $request->ip()
                ]);

                return redirect()->back()->with('error', 'Current password is incorrect.');
            }

            if (Hash::check($newPassword, $user->password)) {
                return redirect()->back()->with('error', 'New password must be different from your current password.');
            }

            DB::beginTransaction();

            try {
                $user->update([
                    'password' => Hash::make($newPassword),
                    'password_changed_at' => now(),
                ]);

                DB::commit();

                Log::info('Password updated successfully', [
                    'user_id' => $user->id,
                    'ip_address' => $request->ip()
                ]);

                return redirect()->back()->with('success', 'Password updated successfully. Please use your new password for future logins.');

            } catch (Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (ValidationException $e) {
            throw $e;
        } catch (Exception $e) {
            DB::rollBack();

            Log::error('Password update error: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()->with('error', 'Failed to update password. Please try again.');
        }
    }

    /**
     * @return RedirectResponse
     */
    public function removeAvatar(): RedirectResponse
    {
        try {
            $user = auth()->user();

            if (!$user->avatar) {
                return back()->with('error', 'No avatar to remove.');
            }

            DB::beginTransaction();
            $this->removeFile($user->avatar);
            $user->update(['avatar' => null]);
            DB::commit();

            Log::info('Avatar removed successfully', [
                'user_id' => $user->id
            ]);

            return back()->with([
                'success' => 'Avatar removed successfully.',
                'avatar_url' => null
            ]);

        } catch (Exception $e) {
            DB::rollBack();

            Log::error('Avatar removal error: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()->with('error', 'Failed to remove avatar. Please try again.');
        }
    }

    /**
     * @param Request $request
     * @return RedirectResponse
     */
    public function submitKyc(Request $request): RedirectResponse
    {
        try {
            $user = auth()->user();
            $existingKyc = $user->kycVerification;

            if ($existingKyc && $existingKyc->status === 'approved') {
                return back()->with('error', 'Your KYC is already approved.');
            }

            if ($existingKyc && $existingKyc->status === 'reviewing') {
                return back()->with('error', 'Your KYC is currently under review.');
            }

            $validator = Validator::make($request->all(), [
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'date_of_birth' => 'required|date|before:today',
                'phone' => 'required|string|max:20',
                'address' => 'required|string|max:500',
                'city' => 'required|string|max:100',
                'state' => 'required|string|max:100',
                'country' => 'required|string|max:100',
                'postal_code' => 'required|string|max:20',
                'document_type' => 'required|in:passport,national_id,drivers_license',
                'document_number' => 'required|string|max:100',
                'front_image' => 'required|image|max:5120|mimes:jpg,jpeg,png',
                'back_image' => 'required|image|max:5120|mimes:jpg,jpeg,png',
                'selfie' => 'required|image|max:5120|mimes:jpg,jpeg,png',
            ], [
                'date_of_birth.before' => 'Date of birth must be before today.',
                'front_image.max' => 'Front image size must not exceed 5MB.',
                'back_image.max' => 'Back image size must not exceed 5MB.',
                'selfie.max' => 'Selfie size must not exceed 5MB.',
            ]);

            if ($validator->fails()) {
                return back()->withErrors($validator)->withInput();
            }

            DB::beginTransaction();

            $frontFile = $request->file('front_image');
            $backFile = $request->file('back_image');
            $selfieFile = $request->file('selfie');

            if (!$frontFile->isValid() || !$backFile->isValid() || !$selfieFile->isValid()) {
                DB::rollBack();
                return back()->with('error', 'One or more uploaded files are corrupted. Please try again.');
            }

            $frontImagePath = $this->move($frontFile, 'kyc');
            $backImagePath = $this->move($backFile, 'kyc');
            $selfiePath = $this->move($selfieFile, 'kyc');

            if ($existingKyc) {
                if ($existingKyc->document_front_path) {
                    $this->removeFile($existingKyc->document_front_path);
                }
                if ($existingKyc->document_back_path) {
                    $this->removeFile($existingKyc->document_back_path);
                }
                if ($existingKyc->selfie_path) {
                    $this->removeFile($existingKyc->selfie_path);
                }
            }

            $kycData = [
                'user_id' => $user->id,
                'first_name' => trim($request->first_name),
                'last_name' => trim($request->last_name),
                'date_of_birth' => $request->date_of_birth,
                'phone' => trim($request->phone),
                'address' => trim($request->address),
                'city' => trim($request->city),
                'state' => trim($request->state),
                'country' => trim($request->country),
                'postal_code' => trim($request->postal_code),
                'document_type' => $request->document_type,
                'document_number' => trim($request->document_number),
                'document_front_path' => $frontImagePath,
                'document_back_path' => $backImagePath,
                'selfie_path' => $selfiePath,
                'status' => 'reviewing',
                'submitted_at' => now(),
                'rejection_reason' => null,
            ];

            if ($existingKyc) {
                $existingKyc->update($kycData);
            } else {
                KycVerification::create($kycData);
            }

            DB::commit();

            Log::info('KYC submitted successfully', [
                'user_id' => $user->id,
                'document_type' => $request->document_type
            ]);

            return back()->with('success', 'KYC verification submitted successfully. We will review your documents within 24-48 hours.');

        } catch (Exception $e) {
            DB::rollBack();

            Log::error('KYC submission error: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()->with('error', 'Failed to submit KYC verification. Please try again.');
        }
    }
}
