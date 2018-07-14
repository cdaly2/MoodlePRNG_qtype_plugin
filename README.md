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
