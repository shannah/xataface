#Xataface Delegate Classes

Xataface applications provide quite a number of mechanisms for customizing application behaviour. One of the most fundamental of these is the *Delegate Class*.  A Delegate class is a class that can be implemented to provide customized functionality to your application by implementing methods that follow naming conventions.

There are two types of delegate classes:

1. **Table Delegate Classes** : Override behaviour pertaining to a particular table.
2. **Application Delegate Class** : Override behaviour pertaining to the entire application.

##What Can You Do With A Delegate Class?

1. Customize Permissions
2. Override portions of the UI with custom HTML content. (Slots)
3. Insert custom HTML content into parts of the UI. (Blocks and Sections)
4. Override the representation of field content in various formats. E.g. HTML, CSV, Text, etc..
5. Implement event handlers or "triggers" to execute custom PHP code during specified events.  E.g. *before insert*, *after insert*, *before update*, *after user registration*, *before handle request*, etc...
6. Add custom initialization to tables and fields.
7. Create custom fields.

There are many other things you can do with delegate classes also.  In fact most tutorials that involve customizing application behaviour will involve delegate classes in some form.

##How to Add an Application Delegate Class to Your App

1. Create a directory named *conf* in your application's root directory.
2. Create a file named *ApplicationDelegate.php* inside your *conf* directory with the following contents:
 
 ~~~
 <?php
 class conf_ApplicationDelegate {
 
 }
 ~~~

3. Verify that your application is picking it up, by implementing a method and checking to make sure that it is executed.  In this example, we'll implement the `beforeHandleRequest()` method, which is called before every HTTP request:

 ~~~
 <?php
 class conf_ApplicationDelegate {
   function beforeHandleRequest(){
      echo "In beforeHandleRequest";
      exit;
   }
 }
 ~~~
Now, try loading your app.  You should see:

 ~~~
 In beforeHandleRequest
 ~~~
in your browser if the delegate class is being picked up.  If you don't see this, then either it isn't being picked up at all, or it isn't finding the `beforeHandleRequest` method.  In this case, see the [troubleshooting](#application-delegate-troubleshooting) section for more details.