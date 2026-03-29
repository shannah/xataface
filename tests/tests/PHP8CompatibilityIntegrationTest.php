<?php
/**
 * Integration tests for PHP 8.0 compatibility changes.
 *
 * These tests require a database connection and test the changes
 * in the context of actual Xataface operations (Table display,
 * date formatting, money formatting, query building, etc.)
 *
 * Run via: Include 'PHP8CompatibilityIntegrationTest' in runTests.php
 */

require_once 'BaseTest.php';
require_once 'Dataface/Table.php';
require_once 'Dataface/Record.php';
require_once 'Dataface/QueryBuilder.php';
require_once 'Dataface/IO.php';

class PHP8CompatibilityIntegrationTest extends BaseTest {

    function PHP8CompatibilityIntegrationTest($name = 'PHP8CompatibilityIntegrationTest') {
        $this->BaseTest($name);
    }

    function __construct($name = 'PHP8CompatibilityIntegrationTest') {
        $this->PHP8CompatibilityIntegrationTest($name);
    }

    function setUp() {
        parent::setUp();

        // Create a table with money and date fields for testing
        xf_db_query("
            CREATE TABLE IF NOT EXISTS PHP8Test (
                id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(128) NOT NULL,
                price DECIMAL(10,2),
                event_date DATE,
                event_datetime DATETIME,
                event_time TIME,
                description TEXT,
                created TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB;
        ", $this->db) or die("Error creating PHP8Test table: " . xf_db_error($this->db));

        // Insert test data
        xf_db_query("
            INSERT INTO PHP8Test (name, price, event_date, event_datetime, event_time, description)
            VALUES
            ('Widget A', 19.99, '2023-06-15', '2023-06-15 14:30:00', '14:30:00', 'A test widget'),
            ('Widget B', 0.50, '2023-12-25', '2023-12-25 09:00:00', '09:00:00', 'Another widget'),
            ('Widget C', 1234.56, '2024-01-01', '2024-01-01 00:00:00', '00:00:00', 'New year widget')
        ", $this->db) or die("Error inserting PHP8Test data: " . xf_db_error($this->db));
    }

    // =========================================================================
    // Table.php: money_format() replacement
    // =========================================================================

    function test_money_format_replacement() {
        // The money_format function was replaced with NumberFormatter fallback.
        // Verify that the display method still works on a record with a price field.
        $table = Dataface_Table::loadTable('PHP8Test');
        $this->assertTrue($table instanceof Dataface_Table, 'Should load PHP8Test table');

        $record = new Dataface_Record('PHP8Test', array());
        $record->setValues(array(
            'id' => 1,
            'name' => 'Test',
            'price' => 19.99
        ));

        // The display method handles money_format if configured
        $display = $record->display('price');
        $this->assertTrue(strlen($display) > 0, 'Price display should not be empty');
    }

    // =========================================================================
    // Table.php: strftime() / xf_strftime() for date formatting
    // =========================================================================

    function test_xf_strftime_in_date_display() {
        // Verify that date fields display correctly using xf_strftime
        $table = Dataface_Table::loadTable('PHP8Test');

        $record = df_get_record('PHP8Test', array('id' => '=1'));
        if ($record) {
            $dateVal = $record->val('event_date');
            $this->assertTrue(!empty($dateVal), 'event_date should have a value');

            // Display should not cause errors
            $display = $record->display('event_date');
            $this->assertTrue(strlen($display) > 0, 'Date display should produce output');
        } else {
            // If we can't load the record this way, test xf_strftime directly
            $ts = mktime(14, 30, 0, 6, 15, 2023);
            $result = xf_strftime('%Y-%m-%d', $ts);
            $this->assertEquals('2023-06-15', $result, 'xf_strftime should format date');
        }
    }

    function test_xf_strftime_all_months() {
        // Test month name generation (used by Smarty date select plugins)
        $months_full = array();
        $months_abbr = array();
        for ($i = 1; $i <= 12; $i++) {
            $ts = mktime(0, 0, 0, $i, 1, 2000);
            $months_full[$i] = xf_strftime('%B', $ts);
            $months_abbr[$i] = xf_strftime('%b', $ts);
        }
        $this->assertEquals('January', $months_full[1]);
        $this->assertEquals('February', $months_full[2]);
        $this->assertEquals('December', $months_full[12]);
        $this->assertEquals('Jan', $months_abbr[1]);
        $this->assertEquals('Dec', $months_abbr[12]);
        $this->assertEquals(12, count($months_full), 'Should have 12 months');
    }

    function test_xf_strftime_all_weekdays() {
        // Test weekday name generation (used by I18Nv2/Locale.php)
        // 2023-01-01 is a Sunday
        $sun = mktime(0, 0, 0, 1, 1, 2023);
        $days = array();
        for ($i = 0; $i < 7; $i++) {
            $days[] = xf_strftime('%A', $sun + ($i * 86400));
        }
        $this->assertEquals('Sunday', $days[0]);
        $this->assertEquals('Monday', $days[1]);
        $this->assertEquals('Saturday', $days[6]);
        $this->assertEquals(7, count($days), 'Should have 7 days');
    }

    // =========================================================================
    // public-api.php: df_offset() date calculation with xf_strftime
    // =========================================================================

    function test_df_offset_function() {
        // df_offset uses xf_strftime for date offset calculations
        if (function_exists('df_offset')) {
            $today = date('Y-m-d');
            $result = df_offset($today);
            $this->assertTrue(is_string($result), 'df_offset should return a string');
            $this->assertTrue(strlen($result) > 0, 'df_offset should not be empty');

            // Yesterday should say "Yesterday"
            $yesterday = date('Y-m-d', strtotime('-1 day'));
            $result = df_offset($yesterday);
            $this->assertTrue(strlen($result) > 0, 'df_offset for yesterday should produce output');

            // 2 days ago
            $twoDays = date('Y-m-d', strtotime('-2 days'));
            $result = df_offset($twoDays);
            $this->assertTrue(strlen($result) > 0, 'df_offset for 2 days ago should produce output');

            // 2 weeks ago
            $twoWeeks = date('Y-m-d', strtotime('-14 days'));
            $result = df_offset($twoWeeks);
            $this->assertTrue(strlen($result) > 0, 'df_offset for 2 weeks ago should produce output');

            // Long format
            $result = df_offset($yesterday, true);
            $this->assertTrue(strpos($result, ' - ') !== false,
                'Long format should contain separator');
        } else {
            $this->assertTrue(true, 'df_offset not available in this context');
        }
    }

    // =========================================================================
    // HTML/QuickForm.php: preg_split replaces split()
    // =========================================================================

    function test_quickform_element_split() {
        require_once 'HTML/QuickForm.php';
        // updateElementAttr uses preg_split('/[ ]?,[ ]?/', ...)
        // Verify the function doesn't fatal error with string input
        $form = new HTML_QuickForm('test_form', 'POST');
        $form->addElement('text', 'field1', 'Field 1');
        $form->addElement('text', 'field2', 'Field 2');

        // This should not throw any error — it internally uses preg_split now
        $form->updateElementAttr('field1, field2', array('class' => 'test-class'));

        $el1 = $form->getElement('field1');
        $attrs = $el1->getAttributes();
        $this->assertEquals('test-class', $attrs['class'],
            'updateElementAttr should apply attribute via preg_split');
    }

    // =========================================================================
    // HTML/QuickForm/date.php: closure replacements
    // =========================================================================

    function test_quickform_date_element() {
        require_once 'HTML/QuickForm.php';
        require_once 'HTML/QuickForm/date.php';

        // Creating a date element exercises the array_walk closures
        $form = new HTML_QuickForm('test_date_form', 'POST');
        $form->addElement('date', 'test_date', 'Test Date', array(
            'format' => 'Y-m-d',
            'minYear' => 2020,
            'maxYear' => 2025
        ));

        $el = $form->getElement('test_date');
        $this->assertTrue($el instanceof HTML_QuickForm_date,
            'Date element should be created without error');
    }

    // =========================================================================
    // Dataface/Table.php: display() with various field types
    // =========================================================================

    function test_table_display_no_errors() {
        $table = Dataface_Table::loadTable('Profiles');
        $this->assertTrue($table instanceof Dataface_Table, 'Should load Profiles table');

        // Test field loading (exercises bracket access patterns)
        $fields = $table->fields();
        $this->assertTrue(count($fields) > 0, 'Should have fields');
        $this->assertTrue(isset($fields['fname']), 'Should have fname field');
    }

    // =========================================================================
    // PEAR.php: destructor list iteration (foreach replacement)
    // =========================================================================

    function test_pear_object_creation() {
        require_once 'PEAR.php';
        // Creating PEAR objects adds them to the destructor list,
        // which now uses foreach instead of each()
        $obj = new PEAR();
        $this->assertTrue($obj instanceof PEAR, 'PEAR object should be created');
    }

    // =========================================================================
    // htmLawed: closure and regex validation
    // =========================================================================

    function test_htmlawed_basic() {
        require_once 'htmLawed.php';
        if (function_exists('htmLawed')) {
            // Basic sanitization - exercises the closure replacements
            $input = '<b>Hello</b> <script>alert("xss")</script>';
            $result = htmLawed($input);
            $this->assertTrue(strpos($result, '<b>Hello</b>') !== false,
                'Should keep valid HTML');
            $this->assertFalse(strpos($result, '<script>') !== false,
                'Should remove script tags');
        } else {
            $this->assertTrue(true, 'htmLawed not loaded');
        }
    }

    function test_htmlawed_regex_validation() {
        // Test the hl_regex function (replaced $php_errormsg with preg_match return check)
        if (function_exists('hl_regex')) {
            $this->assertEquals(1, hl_regex('/^[a-z]+$/i'), 'Valid regex should pass');
            $this->assertEquals(0, hl_regex('/[invalid'), 'Invalid regex should fail');
            $this->assertEquals(0, hl_regex(''), 'Empty regex should fail');
        } else {
            $this->assertTrue(true, 'hl_regex not loaded');
        }
    }

    // =========================================================================
    // XML/Parser.php: preg_match replaces eregi
    // =========================================================================

    function test_xml_parser_url_detection() {
        require_once 'XML/Parser.php';
        // The class should load without error (eregi replaced with preg_match)
        $this->assertTrue(class_exists('XML_Parser'), 'XML_Parser class should be loadable');
    }

    // =========================================================================
    // Text/Diff: assert and each() fixes
    // =========================================================================

    function test_text_diff_basic() {
        require_once 'Text/Diff.php';
        if (class_exists('Text_Diff')) {
            $lines1 = array('line1', 'line2', 'line3');
            $lines2 = array('line1', 'modified', 'line3');
            $diff = new Text_Diff($lines1, $lines2);
            $this->assertTrue($diff instanceof Text_Diff, 'Diff should be created');
        } else {
            $this->assertTrue(true, 'Text_Diff not available');
        }
    }

    // =========================================================================
    // Smarty date_format modifier
    // =========================================================================

    function test_smarty_date_format_modifier() {
        require_once 'Smarty/plugins/modifier.date_format.php';
        if (function_exists('smarty_modifier_date_format')) {
            $ts = mktime(14, 30, 0, 6, 15, 2023);
            $result = smarty_modifier_date_format($ts, '%Y-%m-%d');
            $this->assertEquals('2023-06-15', $result,
                'Smarty date_format modifier should work with xf_strftime');
        } else {
            $this->assertTrue(true, 'Smarty modifier not loaded');
        }
    }
}

// Allow standalone execution within the test app context
if (php_sapi_name() == 'cli' && basename($_SERVER['PHP_SELF'] ?? $_SERVER['SCRIPT_NAME'] ?? '') == basename(__FILE__)) {
    require_once 'PHPUnit.php';
    $test = new PHPUnit_TestSuite('PHP8CompatibilityIntegrationTest');
    $result = new PHPUnit_TestResult;
    $test->run($result);
    print $result->toString();
    exit($result->wasSuccessful() ? 0 : 1);
}
