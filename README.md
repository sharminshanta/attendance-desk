## Install the Application

Run this command from the directory in which you want to install your new Office Attendance Management application.

    sudo git clone https://github.com/sharminex81/attendance-desk
    
* Permit the project folder. Just run this command.
	sudo chmod 777 YOUR_PROJECT_PATH -R
	
* Open the project and Install the composer for installing project requirement. Run this command.
    composer install
    
* Change the config.sample.php file to config.php for application configuration.

* Point your virtual host document root to your new application's `public/` directory.
* Ensure `logs/` is web writeable.

That's it! Now go build something cool.
