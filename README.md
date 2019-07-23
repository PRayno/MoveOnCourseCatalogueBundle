# MoveOnCourseCatalogueBundle
This is a Symfony bundle to manage your MoveOn (https://www.qs-unisolution.com/moveon/) course catalogue.

It provides 2 commands :
- import a course catalogue from a CSV to MoveOn
- deactivate a liste of course in the MoveOn course catalogue

## Installation
Install the library via Composer by running the following command:
`composer require prayno/moveon-course-catalogue-bundle`

## Configuration
This bundle depends on the MoveOnApiBundle so you must include the configuration of this bundle too (https://github.com/PRayno/MoveOnApiBundle#configuration)

Create a config/packages/prayno_moveon_course_catalogue.yaml file in your Symfony application with the following settings :
```yaml
prayno_moveon_course_catalogue:
  moveon_course_object: My\Moveon\Course\Object
  csv:
    delimiter: ""
    latest_date_fields: ['FIELD1','FIELD2'] 
    required_fields: ['FIELD3','FIELD4']
```

- *moveon_course_object* : object implementing the PRayno\MoveOnCourseCatalogueBundle\Course\MoveonCourseInterface to create your own values for MoveOn attributes based on a CSV line (see default in PRayno\MoveOnCourseCatalogueBundle\Course\MoveonCourse)
- *delimiter* : CSV delimiter (default is Tab)
- *latest_date_fields* : array of fieldnames in your CSV file that correspond to the modification dates of the line
- *required_fields* : array of fieldnames in your CSV file that are required to process a line

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

