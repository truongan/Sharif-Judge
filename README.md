#Wecode

Wecode judge, originally forked from[Sharif Judge](https://github.com/truongan/wecode-judge), is a free and open source online judge for programming courses.

Wecode judge employ *docker* to contain and execute the user submitted code. The web interface is written in PHP (CodeIgniter framework) and the main backend is written in BASH.

The full documentation is at https://github.com/truongan/wecode-judge/tree/docs

Download the latest release by cloning this repository.

## Features
  * Multiple user roles (admin, head instructor, instructor, student)
  * Sandboxing using _docker_
  * Cheat detection (similar codes detection) using [Moss](http://theory.stanford.edu/~aiken/moss/)
  * Custom rule for grading late submissions
  * Submission queue
  * Download results in excel file
  * Download submitted codes in zip file
  * _"Output Comparison"_ and _"Tester Code"_ methods for checking output correctness
  * Add multiple users
  * Problem Descriptions (PDF/Markdown/HTML)
  * Rejudge
  * Scoreboard
  * Notifications
  * Code template for "_fill in the blank_" assignments where instructor supply a portion of the code and student finish it.
  * ...

## Requirements

For running Wecode judge, a Linux server with following requirements is needed:

  * Webserver running PHP version 5.3 or later with `mysqli` extension
  * PHP CLI (PHP command line interface, i.e. `php` shell command)
  * MySql or PostgreSql database
  * PHP must have permission to run shell commands using [`shell_exec()`](http://www.php.net/manual/en/function.shell-exec.php) php function (specially `shell_exec("php");`)
  * Docker! (wecode judge can use native tools for compiling and running submitted codes but that's a severe security risk, planned to be removed)
  * It is better to have `perl` installed for more precise time and memory limit and imposing size limit on output of submitted code.

## Installation

  1. Clone latest release from [github repository](https://github.com/truongan/wecode-judge) into a directory with read/write permission. Then put the index.php file in your webserver's serving directory
  2. Take note the location of `system` and `application`  folders, then record their full path in `index.php` file (edit the `$system_path` and `$application_folder` variables).
  3. Create a MySql or PostgreSql database for Wecode judge.
  4. Copy `application/config/database.php.example` to `application/config/database.php` and edit it to suit database connection settings you create in the previous step.
  5. Copy `application/config/config.php.example` to `application/config/config.php` and edit base_url settings. Wecode judge won't try to guess your site url, you have to set this settings.
  6. Make sure `application/cache/Twig` is writable by php process.
  7. Open the main page of Wecode judge in a web browser and follow the installation process.

## Setup the judge

Since wecode-jduge use docker to isolate the submitted code, you have to setup docker and other settings so that judge can call it.
 1. Install docker-ce in your server. The instruction for Ubuntu can be found on docker guide: https://docs.docker.com/install/linux/docker-ce/ubuntu/
 2. Follow docker instruction to allow http user manage docker: https://docs.docker.com/install/linux/linux-postinstall/
 



## After Installation
* **[IMPORTANT]** Move folders `tester` and `assignments` somewhere outside your public directory. Then save their full path in `Settings` page. **These two folders must be writable by PHP.** Submitted files will be stored in `assignments` folder. So it should be somewhere not publicly accessible.
* **[IMPORTANT]** [Secure Wecode judge](https://github.com/truongan/wecode-judge/blob/docs/v1.4/security.md)

* Read the [documentation]https://github.com/truongan/wecode-judge/tree/docs)

## License

GPL v3
