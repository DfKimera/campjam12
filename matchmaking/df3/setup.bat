@echo off
cls
echo -----------------------------------
echo Diesel Framework 3
echo Copyright © LQDI t.image - 2012
echo Programmed by Aryel Tupinambá
echo -----------------------------------
echo Copying DF3 base files and directories...
xcopy /E /F /Y base_app\* ..\
echo Removing base app files from framework directory...
rmdir /S /Q base_app
echo Erasing placeholder files...
cd ../
del /S "df3_placeholder_file"
echo Diesel Framework 3 is ready! Happy coding!
pause