As a temporary measure, before the plugin was created, I have parsed the logs (which are only available for the last 30 days on our server) with the help of chatgpt and have collected the data 

After several iterations and manual corrections, the data I've presented is for a time-window of 2 hours - ie if someone comes to our site with referral id, finishes signing up for the course within 2 hours, 
the referid will be listed in the list. 

The chatgpt prompt was initially as follows.

I have apache access logs, in which can be found GET requests with a referral id, with grep "&referralid=". The access logs lines have timestamps in UTC, of the form

 103.180.45.22 - - [02/Sep/2025:05:20:58 +0000] "GET /course/view.php?id=174&referralid=daf99cca1e3aaaa3de00000304f113b6&code=F_ABCDE_04&student_id=SqZabcdefgkAJ6Jy998877 HTTP/1.1" 303 2329 "https://swayam-plus.swayam2.ac.in/" "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36"

 Additionally, I have course logs from Moodle, where a report provides information about when a user has signed up for a course - the report logs lines are like this: 

 Course short name Course full name Full name with link Time enrolled 

and these times are in Indian Standard Time. Would any of the standard log parsing tools be able to match the referer code to the user/course combination? 
The user course enrolment time could sometimes be a few minutes after the referer timestamp, since the user would need to create an account by filling up a form.

ChatGPT suggested python code, which after several iterations were the two python scripts present here.
