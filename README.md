# SANTO

System to gather data for ontology-driven **S**lot filling tasks with a web-based **AN**notation **TO**ol. 

## Description

Supervised machine learning algorithms require training data whose generation for complex relation extraction tasks tends to be difficult. Being optimized for relation extraction at sentence level, many annotation tools lack in facilitating the annotation of relational structures that are widely spread across the text. This leads to non-intuitive and cumbersome visualizations, making the annotation process unnecessarily time-consuming. We propose SANTO, an easy-to-use, domain-adaptive annotation tool specialized for complex slot filling tasks which may involve problems of cardinality and referential grounding. The web-based architecture enables fast and clearly structured annotation for multiple users in parallel. Relational structures are formulated as templates following the conceptualization of an underlying ontology. Further, import and export procedures of standard formats enable interoperability with external sources and tools.

## Citation

If you use this project please cite
> Hartung M, ter Horst H, Grimm F, Diekmann T, Klinger R, Cimiano P (In Press) 
> In: Proceedings of the 56th Annual Meeting of the Association for Computational Linguistics (System Demonstrations). Association for Computational Linguistics.
available at https://pub.uni-bielefeld.de/publication/2919923

## Requirements

- Apache >= 2.4
- PHP 5.6
- MySQL / MariaDB 5.5

## Installation

1. Clone this repository into your web root.
2. Create a MySQL database and user for the project.
3. Import schema.sql into the database you created induring step 2.
4. Adjust config/annodb.config and provide connection details (hostname, username, password, schema (= database name))  for your installation.
5. In the repository root, create an admin user (replace admin by a username and secret by a password: 
    ```bash
    php php/cli_createuser.php admin secret
    ```
6. Assign curator privileges (by default users can only annotate) for all users that need them by changing the flag in the User table (replace "admin" with the username you want to alter):
    ```bash
    mysql YOURDATABASENAME
    ```
    ```sql
    UPDATE User SET IsCurator = 1 WHERE Mail = "admin";
    ```
7. Upload your ontology descriptor files (see examples) under https://<serveruri>/Upload.html
8. Import a zipped dataset (tokenized publication + annotations). The bulk import script will automatically assign users to their respective publications. Filenames follow the scheme `PublicationName_username.extension`, where extension is `csv` for tokenizations and `annodb` for pre-existing annotations (see example files).
    ```bash
    php php/cli_import.php /path/to/importfile.zip
    ```

## License

See the LICENSE file in the root directory of this repository.

