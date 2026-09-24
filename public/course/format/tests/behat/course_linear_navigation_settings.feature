@core @core_course @core_courseformat @format_topics @format_weeks
Feature: Enable course linear navigation setting
  In order to navigate through the course activities in a linear way
  As an administrator
  I need to control course linear navigation from the course format settings

  Background:
    Given the following "users" exist:
      | username | firstname | lastname |
      | s1       | Student   | 1        |

  @format_singleactivity @format_social
  Scenario Outline: The linear navigation setting is not part of the course settings
    Given the following "courses" exist:
      | fullname | shortname | format   |
      | Course1  | c1        | <format> |
    When I am on the "Course1" "Course" page logged in as "admin"
    And I navigate to "Settings" in current page administration
    And I expand all fieldsets
    Then I should not see "Enable linear navigation"
    And I should not see "Display 'Previous' and 'Next' buttons on activity pages to help learners"

    Examples:
      | format         |
      | topics         |
      | weeks          |
      | singleactivity |
      | social         |

  @javascript
  Scenario Outline: Saving a course does not change its linear navigation
    Given the following config values are set as admin:
      | enablelinearnav | 1 | format_<format> |
    And the following "courses" exist:
      | fullname | shortname | format   |
      | Course1  | C1        | <format> |
    And the following "course enrolments" exist:
      | user | course | role    |
      | s1   | C1     | student |
    And the following "activities" exist:
      | activity | name  | course |
      | page     | Page1 | C1     |
    And I am on the "Course1" "course editing" page logged in as "admin"
    And I expand all fieldsets
    When I press "Save and display"
    # Landing on the course page rather than back on the form proves the course saved.
    Then I should see "Page1"
    And I am on the "Page1" "page activity" page logged in as "s1"
    And the course linear navigation should be visible

    Examples:
      | format |
      | topics |
      | weeks  |

  @javascript
  Scenario Outline: The site setting controls linear navigation on an existing course
    Given the following config values are set as admin:
      | enablelinearnav | 0 | format_<format> |
    And the following "courses" exist:
      | fullname | shortname | format   |
      | Course1  | C1        | <format> |
    And the following "course enrolments" exist:
      | user | course | role    |
      | s1   | C1     | student |
    And the following "activities" exist:
      | activity | name  | course |
      | page     | Page1 | C1     |
    And I am on the "Page1" "page activity" page logged in as "s1"
    And the course linear navigation should not be visible
    When the following config values are set as admin:
      | enablelinearnav | 1 | format_<format> |
    And I am on the "Page1" "page activity" page
    Then the course linear navigation should be visible

    Examples:
      | format |
      | topics |
      | weeks  |

  @javascript
  Scenario Outline: The site setting is applied independently for each course format
    Given the following config values are set as admin:
      | enablelinearnav | <topicsvalue> | format_topics |
    And the following config values are set as admin:
      | enablelinearnav | <weeksvalue>  | format_weeks  |
    And the following "courses" exist:
      | fullname | shortname | format |
      | Course1  | C1        | topics |
      | Course2  | C2        | weeks  |
    And the following "course enrolments" exist:
      | user | course | role    |
      | s1   | C1     | student |
      | s1   | C2     | student |
    And the following "activities" exist:
      | activity | name  | course |
      | page     | Page1 | C1     |
      | page     | Page2 | C2     |
    When I am on the "Page1" "page activity" page logged in as "s1"
    Then the course linear navigation <topicsvisible>
    And I am on the "Page2" "page activity" page
    And the course linear navigation <weeksvisible>

    Examples:
      | topicsvalue | weeksvalue | topicsvisible         | weeksvisible          |
      | 1           | 0          | should be visible     | should not be visible |
      | 0           | 1          | should not be visible | should be visible     |
