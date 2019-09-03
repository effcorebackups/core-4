

                       ○○○○   ○○○○
                     ○○○○○  ○○○○○○
                    ○○○    ○○○
        ○○○○○○○     ○○○○○○○○○○○○○○  ○○○○○○○      ○○○○○○○     ○○ ○○○   ○○○○○○○
      ○○○○   ○○○○   ○○○○○○○○○○○○  ○○○     ○○○  ○○○     ○○○   ○○○    ○○○     ○○○
     ○○○       ○○○  ○○○    ○○○   ○○           ○○         ○○  ○○    ○○         ○○
    ○○○○○○○○○○○○○○  ○○○    ○○○  ○○           ○○           ○○ ○○   ○○○○○○○○○○○○○○
     ○○○            ○○○    ○○○   ○○           ○○         ○○  ○○    ○○
      ○○○○   ○○○○   ○○○    ○○○    ○○○     ○○○  ○○○     ○○○   ○○     ○○○     ○○○
        ○○○○○○○     ○○○    ○○○      ○○○○○○○      ○○○○○○○     ○○       ○○○○○○○
                  ○○○○   ○○○○
                ○○○○   ○○○○


About the project
=====================================================================


…


Security
---------------------------------------------------------------------


…


Core: NoSQL
---------------------------------------------------------------------


…


Core: SQL
---------------------------------------------------------------------

MySQL and SQLite can be used as SQL storages.
Storage connection and data retrieval will initialize only if required.
Denying access to SQL storage will not raise an error, but will only
make inaccessible part of the possibilities (for example, sessions
and login will be disconnected, and on the pages with election
"0 results" will be displayed).

The following are supported:
- checks;
- prepared queries (no chance for SQL-injections);
- transactions (begin, roll_back, commit);
- collations (nocase, binary);
- constraints (primary, unique, foreign with cascade action);
- index and unique index;
- connections to remote storages via manual initialization process;
- table prefixes.

Support for cascading foreign key actions:
- on update: "cascade" (not tested feature: "restrict", "no action");
- on delete: "cascade" (not tested feature: "restrict", "no action").

Cross-platform field types are supported:
- autoincrement;
- varchar;
- integer;
- real;
- time;
- date;
- datetime;
- boolean (as integer: 0|1);
- blob.

Other types allowed but not tested.
List of the tested types is sufficient for most tasks.
We recommend to use only tested types for cross-platform compatibility reasons.
Distributed queries to remote storages not supported.

The main focus is on ANSI SQL.
PostgreSQL was excluded during development as Web RDBMS with
least compatible of ANSI standards.

It was decided not to use field of type timestamp.
Instead, we recommended to use a field of type datetime.
This field has a wide range of acceptable values (from "0000-01-01"
to "9999-12-31"), and also does not depend on the time zone.
When adding data to the server, dates should be converted to time zone UTC±0:00.
Instead of the original timestamp type, it's recommended
to use the integer type.



CSS, JS, SASS, LESS
---------------------------------------------------------------------


…


Event model
---------------------------------------------------------------------

…


Web server
---------------------------------------------------------------------


…


Caching
---------------------------------------------------------------------


…


Licensing
---------------------------------------------------------------------

The system is open and free.
The system is not in the public domain.
Anyone can create a website, portal or service on the basis of it,
both personally and for any customer.
However, you cannot distribute system files in their original or
modified form or in conjunction with anything else.

