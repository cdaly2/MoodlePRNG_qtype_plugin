# MoodlePRNG_qtype_plugin
Support for writing PRNG (random parameter) questions for a Moodle quiz

PRNG questions allow us to create a question template but randomise the parameters. Moodle already allows us
to do that. However, using a programming language allows us to generate much more sophisticated questions.
The idea of this plugin is to abstract away the administrative details of the quiz and allow a question
designer to focus on the two important parts of any question, namely creating the question specification and marking the question.

Here is an example of this type of question: https://moodle.org/mod/forum/discuss.php?d=373315#p1505170

This plugin is very experimental. It works for me but caveat emptor.

Rational
========
Moodle is very powerful but it is also complex and it is programmed in PHP which I don't happen to like.
However, I wanted to be able to create programmable questions and I developed a simple interface to
allow me to do this.

I do this by developing a simple protocol to generate a question specification (i.e. the question text)
and to generate the correct answer and to mark a student's response.

I hacked the shortanswer qtype plugin and I took over three items of functionality.
1. In renderer.php, where the spec is requested, I access a url to retrieve the question specification.
2. In question.php, where the correct answer is requested, I forward to another url.
3. When a question is being marked, I take the response and forward it to the url to mark the response.

The code that sits behind these urls can be in pretty much any programming language.
The language that I use in the example here happens to be Python, but it doesn't need to be.

Installation
============
This was installed on a Vanilla Ubuntu 18.04 server with Moodle version 2018051701.00

1. Take the code in the cdsa directory and add it to moodle/question/type/.
   Logging in as moodle admin, you will be asked to install the new plugin. Install it.

2. The Python cgi directory is hard coded as WWWROOT/pycgi/question/
   The categories are then used to specify the actual location of the question. For this question
   I used the default category 'Default for test'. Spaces are removed and any parent directories
   are added to get 
      'top/Defaultfortest'
   Finally, the question name is added and so the complete directory is:
      WWWROOT/pycgi/question/top/Defaultfortest/crypt_demo/
   Ensure that the python program pq is in this directory.

3. Enable cgi for the directory pycgi.

4. You can test this, by going to the url directly (you will probably need to change the hostname):
      http://192.168.56.101/pycgi/question/top/Defaultfortest/crypt_demo/pq

   Assuming cgi is working, this will give the following error message:
      "Error: /pycgi/question/top/Defaultfortest/crypt_demo/pq not parsed properly"

   You need to add a command and two integers. A command will be 'spec', 'ans' or 'mark'.
   The two integers are the student_id and the course_id.
   Assuming that there is a student with id 2 on a course with id 3, then you can see the spec of your question with
      http://192.168.56.101/pycgi/question/top/Defaultfortest/crypt_demo/pq/spec/2_3

   You can see the answer by changing spec to ans:
      http://192.168.56.101/pycgi/question/top/Defaultfortest/crypt_demo/pq/ans/2_3
   And you can mark a response by changing ans to mark and by adding a query string "?resp=" . $response
   as follows:
      http://192.168.56.101/pycgi/question/top/Defaultfortest/crypt_demo/pq/mark/2_2?resp=keep+your+tongue

4. Now, back at Moodle, create a quiz, add a cdsa question, and set the name to be crypt_demo. You will
  need to create one answer with a mark of 100% but that answer will be ignored (this plugin is experimental).
   Assuming that you can access the Python program through the urls, the cdsa plugin should also be able to
   access the urls. The only problem might be some incorrect directories. If you turn on developer debugging,
   You will see which directory that cdsa expects to find the question.

Obviously, as currently implemented, it would be easy for students to find the question and the answer.
   The Python program should be adapted to ignore all queries that don't come from a Moodle question page.
   
   TODO
   ====
   Fix the PHP qtype plugin so that it looks like normal code.
   It should allow the user to modify the directory of the url and the size of the answer box. It should not require the user to enter a text description or to supply any answers (which will be ignored anyway).
   I assume that many parts of the showrtanswer plugin are irrelevant and can be removed.
   
   The Python code should be adapted so that it inherits functionality from a super class.
   It should refuse connections that don't come from the Moodle quiz page as that might allow students
   to directly access the answer.
   Debugging of these questions is currently not trivial. This should be fixed.
   Maybe use a Python web framework such as Flask as the cgi solution uses more resources.
