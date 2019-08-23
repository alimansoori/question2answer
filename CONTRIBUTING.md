# How to contribute

As of version 1.6.3, all development of [IlyaIdea](https://projekt.ir/) will take place through GitHub. Bug reports and pull requests are encouraged, provided they follow these guidelines.


## Bug reports (issues)

If you find a bug (error) with IlyaIdea, please [submit an issue here](https://github.com/ilya/question2answer/issues). Be as descriptive as possible: include exactly what you did to make the bug appear, what you expect to happen, and what happened instead. Also include your PHP version and MySQL version. Remember to check for similar issues already reported.

If you think you've found a security issue, you can responsibly disclose it to us using the [contact form here](https://projekt.ir/feedback.php).

Note that general troubleshooting issues such as installation or how to use a feature should continue to be asked on the [IlyaIdea Q&A](https://projekt.ir/ilya/).


## Pull requests

If you have found the cause of the bug in the ILYA code, you can submit the patch back to the ILYA repository. Create a fork of the repo, make the changes in your fork, then submit a pull request. Bug fix pull requests must be targeted to the **`bugfix`** branch. PRs for new features or large code changes must be made to the **`dev`** branch.

If you wish to implement a feature, you should start a discussion on the [IlyaIdea Q&A][QA] first. We welcome all ideas but they may not be appropriate for the ILYA core. Consider whether your idea could be developed as a plugin.


## Coding style

From 1.7 onwards a new coding style has been implemented that is more in line with other projects. All PHP code should use these guidelines:

- PHP code should start with `<?php` (almost always the very first line). The closing tag `?>` should be omitted to avoid accidental whitespace output.
- PHP files should use UTF-8 encoding without BOM (this is usually default in most text editors).
- Trailing whitespace (tabs or spaces at the end of lines) should not be present. Any advanced text editor should be able to do this automatically when saving. (For Sublime Text you can add the option `"trim_trailing_white_space_on_save": true` to your preferences. In Notepad++ you can press Alt+Shift+S.)
- Use tabs for indenting. Each file should start at level 0 (i.e. no indentation).
- Functions should use a DocBlock-style comment.
- Operators (`=`, `+` etc) should have a space either side.
- Control structure keywords (`if`, `else`, `foreach` etc) should have a space between them and the opening parenthesis.
- Opening braces for classes and functions should be on the next line.
- Opening braces for control structures should be on the same line. All control structures should use braces.

If in doubt, follow the style of the surrounding code. Code examples can be found in the [ILYA docs here](http://docs.question2answer.org/contribute/).


## Documentation

Please see the repository [ilya.github.io](https://github.com/ilya/ilya.github.io/) which automatically produces the documentation website [docs.question2answer.org](http://docs.question2answer.org/).
