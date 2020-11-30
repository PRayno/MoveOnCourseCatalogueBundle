# MoveOnCourseCatalogueBundle
This is a Symfony bundle to manage your MoveOn (https://www.qs-unisolution.com/moveon/) course catalogue.

It provides 2 commands :
- import a course catalogue from a CSV file to MoveOn
- deactivate a list of courses in the MoveOn course catalogue

## Installation
Install the library via Composer by running the following command:
`composer require prayno/moveon-course-catalogue-bundle`

## Configuration
This bundle depends on the MoveOnApiBundle so you must include the configuration of this bundle too (https://github.com/PRayno/MoveOnApiBundle#configuration)

Create a config/packages/prayno_moveon_course_catalogue.yaml file in your Symfony application with the following settings :
```yaml
prayno_moveon_course_catalogue:
  csv:
    delimiter: ""
    latest_date_fields: ['FIELD1','FIELD2'] 
    required_fields: ['FIELD3','FIELD4']

  sub_institution:
    code_field: "fieldname"
    main_institution_id: 1

  academic_periods:
    code1: id
    code2: id

  update_courses_modified_by : ["User1-firstname, User1-lastname","User2-firstname, User2-lastname"]
  course_identifier_regex: "/regex/"

  excluded_lines:
    key: ["value1","value2"]
```

- *delimiter* : CSV delimiter (default is Tab)
- *latest_date_fields* : array of fieldnames in your CSV file that correspond to the modification dates of the line
- *required_fields* : array of fieldnames in your CSV file that are required to process a line
- *code_field* : fieldname of the sub_institution code in MoveOn institution db table
- *main_institution_id* : parent institution id the sub institutions are linked to
- *academic_periods* : array of academic period code used to link CSV course with academic period : id (eg. 1S2019/20: 123456)
- *update_courses_modified_by* : only the courses modified by these users (Lastname, Firstname) will be affected by update (to avoid overriding of local modifications)
- *course_identifier_regex* : regex of the identifier (default external_id) used to deactivate the courses in MoveOn db which are no longer in the CSV
- *excluded_lines* : lines to be excluded from the process (optional). 

## Usage

### Import from CSV
This command will create or update courses from your CSV file. The comparison for update is based on the field provided in the *getIdentifier()* method of your MoveOnCourse object.

`bin/console moveon:course-catalog:update path/to/csv/file.csv 2019-01-01`
The first argument is the path of your CSV file and the second one, the minimum date to process the line (default value : yesterday)

### Deactivate courses
This command will deactivate courses based on your search criteria.

`bin/console  moveon:course-catalog:deactivate '{myquery}'`

Example of queries :
```
'{\"field\":\"catalogue_course.id\",\"op\":\"eq\",\"data\":\"1234\"}'
'{\"field\":\"catalogue_course.start_academic_period\",\"op\":\"eq\",\"data\":\"1er semestre 2018/19\"}'
'{\"field\":\"catalogue_course.is_active\",\"op\":\"eq\",\"data\":\"1\"},{\"field\":\"catalogue_course.start_academic_period\",\"op\":\"eq\",\"data\":\"1er semestre 2017/18\"}'
```

## Customization

You can customize the course object to suit your needs by creating a class implementing the PRayno\MoveOnCourseCatalogueBundle\Course\MoveonCourseInterface (or extending the default PRayno\MoveOnCourseCatalogueBundle\Course\MoveonCourse).
You have to create a function with the snakecase name of the MoveOn Course attribute you want to customize like in the following example :
```php
use PRayno\MoveOnCourseCatalogueBundle\Course\MoveonCourse;

class MyCustomMoveOnCourse extends MoveonCourse
{
    protected function setName(array $row)
    {
        return $row["FIELD1"]." ~~ ".$row["FIELD2"];    
    }
```

Then declare your class in services.yml and copy the "academic_periods" parameters in your app parameters :
```yaml
    prayno_moveon_course_catalogue.course:
        class: MyCustomMoveOnCourseNamespace\MyCustomMoveOnCourse
        arguments:
            $academicPeriods: '%academic_periods%'
```
This allows you to inject other services when processing the course catalogue