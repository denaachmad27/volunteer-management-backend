<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WhatsappSetting;
use App\Models\Complaint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class WhatsappController extends Controller
{
    /**
     * Get WhatsApp settings
     */
    public function getSettings()
    {
        $settings = WhatsappSetting::getSettings();
        
        return response()->json([
            'status' => 'success',
            'data' => $settings
        ]);
    }

    /**
     * Update WhatsApp settings
     */
    public function updateSettings(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'session_name' => 'required|string|max:255',
            'is_active' => 'boolean',
            'default_message_template' => 'nullable|string',
            'department_mappings' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $settings = WhatsappSetting::getSettings();
        $settings->update($request->only([
            'session_name',
            'is_active',
            'default_message_template',
            'department_mappings'
        ]));

        return response()->json([
            'status' => 'success',
            'message' => 'Settings updated successfully',
            'data' => $settings
        ]);
    }

    /**
     * Get QR Code for WhatsApp login
     */
    public function getQRCode()
    {
        try {
            $response = $this->callWhatsAppBridge('GET', '/qr-code');
            
            $settings = WhatsappSetting::getSettings();
            
            if ($response && isset($response['data'])) {
                // Update local settings with bridge response
                $settings->update([
                    'qr_code' => $response['data']['qr_code'],
                    'is_connected' => $response['data']['is_ready']
                ]);
                
                return response()->json([
                    'status' => 'success',
                    'data' => $response['data']
                ]);
            }
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get QR code from WhatsApp service'
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'WhatsApp service is not available: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Initialize WhatsApp session
     */
    public function initializeSession(Request $request)
    {
        try {
            $settings = WhatsappSetting::getSettings();
            
            $response = $this->callWhatsAppBridge('POST', '/initialize', [
                'session_name' => $settings->session_name
            ]);
            
            if ($response && $response['success']) {
                return response()->json([
                    'status' => 'success',
                    'message' => $response['message'],
                    'data' => [
                        'session_name' => $settings->session_name
                    ]
                ]);
            }
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to initialize WhatsApp session'
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'WhatsApp service is not available: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Disconnect WhatsApp session
     */
    public function disconnect()
    {
        try {
            $response = $this->callWhatsAppBridge('POST', '/disconnect');
            
            $settings = WhatsappSetting::getSettings();
            $settings->update([
                'is_connected' => false,
                'session_data' => null,
                'qr_code' => null
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'WhatsApp disconnected successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to disconnect WhatsApp: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send WhatsApp message to department
     */
    public function sendToDepartment(Request $request, $complaintId)
    {
        $validator = Validator::make($request->all(), [
            'department_category' => 'required|string',
            'custom_message' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $complaint = Complaint::with('user')->find($complaintId);
        if (!$complaint) {
            return response()->json([
                'status' => 'error',
                'message' => 'Complaint not found'
            ], 404);
        }

        $settings = WhatsappSetting::getSettings();
        $department = $settings->getDepartmentForCategory($request->department_category);
        
        if (!$department || empty($department['phone_number'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Department contact not configured for this category'
            ], 400);
        }

        try {
            // Format message using bridge server
            $formatResponse = $this->callWhatsAppBridge('POST', '/format-complaint-message', [
                'complaint' => $complaint->toArray(),
                'template' => $request->custom_message ?: $settings->default_message_template,
                'department' => $department
            ]);

            if (!$formatResponse || !$formatResponse['success']) {
                throw new \Exception('Failed to format message');
            }

            $formattedMessage = $formatResponse['formatted_message'];

            // Send message via bridge server
            $sendResponse = $this->callWhatsAppBridge('POST', '/send-message', [
                'phone_number' => $department['phone_number'],
                'message' => $formattedMessage
            ]);

            if (!$sendResponse || !$sendResponse['success']) {
                throw new \Exception($sendResponse['error'] ?? 'Failed to send message');
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Message sent to department successfully',
                'data' => [
                    'department' => $department['department_name'],
                    'phone_number' => $department['phone_number'],
                    'message_id' => $sendResponse['messageId'] ?? null,
                    'message_preview' => substr($formattedMessage, 0, 100) . '...'
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to send WhatsApp message: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test WhatsApp connection
     */
    public function testConnection()
    {
        try {
            $response = $this->callWhatsAppBridge('POST', '/test-connection');
            
            if ($response && $response['success']) {
                // Update local settings
                $settings = WhatsappSetting::getSettings();
                $settings->update([
                    'is_connected' => true
                ]);

                return response()->json([
                    'status' => 'success',
                    'message' => $response['message'],
                    'data' => $response['data']
                ]);
            }
            
            return response()->json([
                'status' => 'error',
                'message' => 'WhatsApp connection test failed'
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'WhatsApp service is not available: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Call WhatsApp Bridge Server
     */
    private function callWhatsAppBridge($method, $endpoint, $data = null)
    {
        $bridgeUrl = env('WHATSAPP_BRIDGE_URL', 'http://localhost:3001');
        $url = $bridgeUrl . $endpoint;

        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json'
        ]);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        } elseif ($method === 'PUT') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);

        if ($error) {
            throw new \Exception('cURL error: ' . $error);
        }

        if ($httpCode >= 400) {
            throw new \Exception('HTTP error: ' . $httpCode);
        }

        return json_decode($response, true);
    }

}