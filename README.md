# BTC.com - Optimal Fee

This tiny PHP web-page parses the statistics from https://btc.com/stats/unconfirmed-tx and determines the most optimal transaction fee in satoshis/byte.

## Install

1. Clone the repo.
2. Run `composer install`
3. Fire up a web server. For example, run `php -S localhost:9000`

## Motivation

The app is actually of no use for me.

The real reason of writing it was that my friend gave me his initial version of this app and asked for a code review. You can find the original version of the app in `original.php`.

So, this repo is my attempt to rewrite it myself. Also, I wrote a more or less descriptive review regarding the main issues with the original code down below.

## Review

### Summary

Remember about the "design" part of the process of "code design". You should treat it the same way you design user interfaces or text articles.

First and foremost, high-level programming languages exist for the sake of easier **communication** between those who write the code. The fact it's executable by computers is secondary.

Do spend time to make the code clean, readable, and approachable by others or even future you.

### Code organization

**One large file.** Using one single big file is usually harmful because it's harder to read and navigate. Although it makes it easy to deploy the app, putting different code organizational units (such as classes) into different files will help with maintainability.

**Separation of concerns.** Most of the functions do a lot of different things at once. It makes code much harder to reason about. Instead, it's a good idea to split your code into separate **independent** modules which focus on doing one job. It's also crucial when it comes to unit-testing.

For example, fetching remote content and parsing it are two completely different operations.

**Mixing different levels of abstraction.** This is related to the previous point, but not exactly the same. What this means is that you shouldn't mix in low-level details into your higher-level functions. And vice versa.

For example, higher-level response-parsing code might not need to contain the low-level details like exact RegEx or the DOM-query. Or, your higher-level logging function shouldn't contain details about sending an Email or writing to the file. Like the real `mail` or `file_put_contents` calls.

Hint. If you feel like writing a code comment, probably you should actually move that logic into a separate self-descriptive code unit like a separate function.

**Overuse of configuration.** What is considered configuration in the original code is actually implementation details. Usually, configuration makes sense in two situations. Either you have multiple installations and you want them to behave in a slightly different way or you are handing off your app to someone else and you want them to have an easy way to tweak the behavior.

I've assumed it's neither.

**Unit-testing.** If the code is going to live for years and be changed many times, unit-testing is a must. Not only does the original version has no tests, but it's also impossible to write them. So, make your code easily-testable and actually write the tests.

I should note that I didn't write unit-tests either due to free time issues. The main purpose was to rewrite the code itself. However, I made it very simple to add tests for the existing implementation.

### Structure and control

**Globals.** Globals should be considered a no-no in almost all cases. There's plenty information on this on the web. In short, shared data make it much harder to track the data flow and figure out what came from where. Also, you don't want every module be able to modify that shared data.

**Overly-defensive programming.** A great article covering this topic: https://hackernoon.com/overly-defensive-programming-e7a1b3d234c2

**Error-handling.** The flow of errors in the app should be simple and rather centralized than distributed. In languages such as PHP, you achieve it with exceptions. In this case, they make it easier to send all the errors up the stack and control them in a centralized manner.

**Lack of well-defined data structures.** This is not about static vs dynamic typing. Rather, it might be very helpful to define data-structures (classes in PHP) for your intermediate data just for the sake of readability. Assoc arrays are simple and easy to get started with, but it's usually hard to tell their shape while reading the code. Knowing that something is an instance of a simple data-only class helps with readability.

### Code styles

**Style inconsistency.** You should always be consistent in how your code looks like. White spaces, indentation, brackets, naming style, and so on. This makes it much easier for the reader to focus the *meaning* of the code instead of its look.

You can use linters and/or auto-formatters for this purpose.

### Implementation details

**Parsing HTML with RegEx.** Although this can a good-enough solution in some cases, RegEx expressions drastically affect your code readability compared to DOM-queries and simple loops. In some cases, it also affects performance. Read this for ~~more information~~ fun: https://stackoverflow.com/questions/1732348/regex-match-open-tags-except-xhtml-self-contained-tags.

**Cache strategy.** The app contains logic "if an error occurs, show the last successful result". For this to work nice and look simple, it's just enough to cache your final HTML of the latest successful visit. No need to cache the data which your final HTML depends on.

**HTML in PHP.** The way original code generates HTML is quite hard to reason about and modify. Instead, you can use the "PHP in HTML" approach. Which is basically defining a separate file with is mostly HTML with some PHP snippets and then inline-include it into a safely-controlled function.

For more complicated cases, of course, you can use template engines, but that should be really put off as far as you can.

## Notes on the alternative implementation

The implementation is not 1:1 copy of the original code.

The user interface has been changed in a few small ways.

Some minor features have been cut off. But guess what? When the code is easy to maintain, adding new functionality or changing the existing behavior is a breeze.
