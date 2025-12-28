<?php
use PHPUnit\Framework\TestCase;

/**
 * Basic integration test for get_user_shift.php.
 * Requires the local server to be running and accessible.
 */
class GetUserShiftTest extends TestCase {
    public function testEndpointReturnsWorkingFrom()
    {
        $url = getenv('TEST_API_URL') ?: 'http://localhost/attendance/attendance_api/get_user_shift.php?user_id=3';
        $opts = ['http' => ['timeout' => 5]];
        $context = stream_context_create($opts);
        $resp = @file_get_contents($url, false, $context);
        $this->assertNotFalse($resp, 'HTTP request failed; ensure server is running and URL is correct: ' . $url);
        $data = json_decode($resp, true);
        $this->assertIsArray($data, 'Response is not valid JSON');
        $this->assertArrayHasKey('status', $data);
        $this->assertEquals('success', $data['status']);
        $this->assertArrayHasKey('working_from', $data);
    }
}
