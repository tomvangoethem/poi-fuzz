**Warning:** *contains crappy code*

## POI-Fuzz

The goal of this little tool is to find (additional entry points to) chains that can be used in a PHP Object Injection exploit, with just built-in classes (in PHP versions before 5.6.12, 5.5.28, and 5.4.4, this could be done by chaining `DateTime::date` (which triggers `__toString()`, `Exception::previous` (which triggers `__call()`) and `SoapClient` (which could be used to [perform XXE](https://cve.mitre.org/cgi-bin/cvename.cgi?name=CVE-2013-1643)).

## Run

You can run the fuzzer like this:

```
php fuzz.php 2>/dev/null | grep -v '^---' | sort -u
```

## Limitations/Todo

* Only fuzzes "top-level" object properties
* Very naive fuzzing method (replacing one object property at a time)
* Only tries unserialization of classes that can be serialized
* Only tries fuzzing of classes that were "easy" to instantiate (e.g. only a non-WSDL version of `SoapClient` gets fuzzed)
