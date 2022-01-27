<?php

/**
 * This file is part of fides, a Matchory application.
 *
 * Unauthorized copying of this file, via any medium, is strictly prohibited.
 * Its contents are strictly confidential and proprietary.
 *
 * @copyright 2020–2022 Matchory GmbH · All rights reserved
 * @author    Moritz Friedrich <moritz@matchory.com>
 */

declare(strict_types=1);

namespace App\Cryptography;

use DateTimeImmutable;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Process\Exception\LogicException;
use Symfony\Component\Process\Exception\ProcessSignaledException;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;

use function array_map;
use function array_slice;
use function assert;
use function count;
use function explode;
use function file_get_contents;
use function implode;
use function in_array;
use function is_array;
use function is_numeric;
use function is_string;
use function preg_replace;
use function trim;
use function ucfirst;

use const PHP_EOL;

/**
 * SSH Keygen
 *
 * Wrapper around the ssh-keygen commandline utility.
 *
 * @bundle App\Cryptography
 * @see    https://man.openbsd.org/ssh-keygen.1
 */
final class SshKeygen
{
    public const CA_MODE_HOST = 'host';

    public const CA_MODE_USER = 'user';

    public const DEFAULT_RSA_VARIANT = self::RSA_VARIANT_RSA2_512;

    public const DEFAULT_TYPE = self::TYPE_ED25519;

    public const EXECUTABLE = 'ssh-keygen';

    public const MAX_PRINCIPALS = 256;

    public const MAX_VALIDITY = 2_147_483_647;

    /**
     * Forces the execution of command instead of any shell or command specified
     * by the user when the certificate is used for authentication.
     *
     * @see https://man.openbsd.org/ssh-keygen.1#force-command
     */
    public const OPTION_FORCE_COMMAND = 'force-command';

    /**
     * Disable ssh-agent(1) forwarding (permitted by default).
     *
     * @see https://man.openbsd.org/ssh-keygen.1#no-agent-forwarding
     * @see https://man.openbsd.org/ssh-agent.1
     */
    public const OPTION_NO_AGENT_FORWARDING = 'no-agent-forwarding';

    /**
     * Disable port forwarding (permitted by default).
     *
     * @see https://man.openbsd.org/ssh-keygen.1#no-port-forwarding
     */
    public const OPTION_NO_PORT_FORWARDING = 'no-port-forwarding';

    /**
     * Disable PTY allocation (permitted by default).
     *
     * @see https://man.openbsd.org/ssh-keygen.1#no-pty
     */
    public const OPTION_NO_PTY = 'no-pty';

    /**
     * Do not require signatures made using this key to include demonstration of
     * user presence (e.g. by having the user touch the authenticator). This
     * option only makes sense for the FIDO authenticator algorithms ecdsa-sk
     * and ed25519-sk.
     *
     * @see https://man.openbsd.org/ssh-keygen.1#no-pty
     */
    public const OPTION_NO_TOUCH_REQUIRED = 'no-touch-required';

    /**
     * Disable execution of ~/.ssh/rc by sshd(8) (permitted by default).
     *
     * @see https://man.openbsd.org/ssh-keygen.1#no-user-rc
     * @see https://man.openbsd.org/sshd.8
     */
    public const OPTION_NO_USER_RC = 'no-user-rc';

    /**
     * Disable X11 forwarding (permitted by default).
     *
     * @see https://man.openbsd.org/ssh-keygen.1#no-x11-forwarding
     */
    public const OPTION_NO_X11_FORWARDING = 'no-x11-forwarding';

    /**
     * Allows ssh-agent(1) forwarding.
     *
     * @see https://man.openbsd.org/ssh-keygen.1#permit-agent-forwarding
     * @see https://man.openbsd.org/ssh-agent.1
     */
    public const OPTION_PERMIT_AGENT_FORWARDING = 'permit-agent-forwarding';

    /**
     * Allows port forwarding.
     *
     * @see https://man.openbsd.org/ssh-keygen.1#permit-port-forwarding
     */
    public const OPTION_PERMIT_PORT_FORWARDING = 'permit-port-forwarding';

    /**
     * Allows PTY allocation.
     *
     * @see https://man.openbsd.org/ssh-keygen.1#permit-pty
     */
    public const OPTION_PERMIT_PTY = 'permit-pty';

    /**
     * Allows execution of ~/.ssh/rc by sshd(8).
     *
     * @see https://man.openbsd.org/ssh-keygen.1#permit-user-rc
     * @see https://man.openbsd.org/sshd.8
     */
    public const OPTION_PERMIT_USER_RC = 'permit-user-rc';

    /**
     * Allows X11 forwarding.
     *
     * @see https://man.openbsd.org/ssh-keygen.1#permit-x11-forwarding
     */
    public const OPTION_PERMIT_X11_FORWARDING = 'permit-x11-forwarding';

    /**
     * Restrict the source addresses from which the certificate is considered
     * valid. The address_list is a comma-separated list of one or more
     * address/netmask pairs in CIDR format.
     *
     * @see https://man.openbsd.org/ssh-keygen.1#source-address
     */
    public const OPTION_SOURCE_ADDRESS = 'source-address';

    /**
     * Require signatures made using this key to indicate that the user was
     * first verified. This option only makes sense for the FIDO authenticator
     * algorithms ecdsa-sk and ed25519-sk. Currently, PIN authentication is the
     * only supported verification method, but other methods may be supported
     * in the future.
     *
     * @see https://man.openbsd.org/ssh-keygen.1#verify-required
     */
    public const OPTION_VERIFY_REQUIRED = 'verify-required';

    /**
     * Specifies the number of bits in the key to create. For RSA keys, the
     * minimum size is 1024 bits and the default is 3072 bits. Generally, 3072
     * bits is considered sufficient. DSA keys must be exactly 1024 bits as
     * specified by FIPS 186-2. For ECDSA keys, the -b flag determines the key
     * length by selecting from one of three elliptic curve sizes: 256, 384 or
     * 521 bits. Attempting to use bit lengths other than these three values for
     * ECDSA keys will fail. ECDSA-SK, Ed25519 and Ed25519-SK keys have a fixed
     * length and the -b flag will be ignored.
     *
     * @see https://man.openbsd.org/ssh-keygen.1#b
     */
    public const PARAM_BITS = '-b';

    /**
     * Show the bubblebabble digest of specified private or public key file.
     *
     * @see https://man.openbsd.org/ssh-keygen.1#B
     */
    public const PARAM_BUBBLEBABBLE_DIGEST = '-B';

    /**
     * When used in combination with -s, this option indicates that a CA key
     * resides in a ssh-agent(1). See the CERTIFICATES section for more
     * information.
     *
     * @see https://man.openbsd.org/ssh-keygen.1#U
     * @see https://man.openbsd.org/ssh-keygen.1#CERTIFICATES
     */
    public const PARAM_CA_FROM_AGENT = '-U';

    /**
     * Certify (sign) a public key using the specified CA key. Please see the
     * CERTIFICATES section for details. When generating a KRL, -s specifies a
     * path to a CA public key file used to revoke certificates directly by key
     * ID or serial number. See the KEY REVOCATION LISTS section for details.
     *
     * @see https://man.openbsd.org/ssh-keygen.1#s
     * @see https://man.openbsd.org/ssh-keygen.1#CERTIFICATES
     */
    public const PARAM_CA_KEY = '-s';

    /**
     * Specify the key identity when signing a public key. Please see the
     * CERTIFICATES section for details.
     *
     * @see https://man.openbsd.org/ssh-keygen.1#I
     * @see https://man.openbsd.org/ssh-keygen.1#CERTIFICATES
     */
    public const PARAM_CERTIFICATE_IDENTITY = '-I';

    /**
     * Requests changing the passphrase of a private key file instead of
     * creating a new private key. The program will prompt for the file
     * containing the private key, for the old passphrase, and twice for the
     * new passphrase.
     *
     * @see https://man.openbsd.org/ssh-keygen.1#p
     */
    public const PARAM_CHANGE_PASSPHRASE = '-p';

    /**
     * Test whether keys have been revoked in a KRL. If the -l option is also
     * specified then the contents of the KRL will be printed.
     *
     * @see https://man.openbsd.org/ssh-keygen.1#Q
     */
    public const PARAM_CHECK_KRL = '-Q';

    /**
     * find-principals:
     * Find the principal(s) associated with the public key of a signature,
     * provided using the -s flag in an authorized signers file provided using
     * the -f flag. The format of the allowed signers file is documented in the
     * ALLOWED SIGNERS section below. If one or more matching principals are
     * found, they are returned on standard output.
     *
     * match-principals:
     * Find principal matching the principal name provided using the -I flag in
     * the authorized signers file specified using the -f flag. If one or more
     * matching principals are found, they are returned on standard output.
     *
     * check-novalidate:
     * Checks that a signature generated using ssh-keygen -Y sign has a valid
     * structure. This does not validate if a signature comes from an authorized
     * signer. When testing a signature, ssh-keygen accepts a message on
     * standard input and a signature namespace using -n. A file containing the
     * corresponding signature must also be supplied using the -s flag.
     * Successful testing of the signature is signalled by ssh-keygen returning
     * a zero exit status.
     *
     * sign:
     * Cryptographically sign a file or some data using an SSH key. When
     * signing, ssh-keygen accepts zero or more files to sign on the
     * command-line - if no
     * files are specified then ssh-keygen will sign data presented on standard
     * input. Signatures are written to the path of the input file with “.sig”
     * appended, or to standard output if the message to be signed was read from
     * standard input.
     * The key used for signing is specified using the -f option and may refer
     * to either a private key, or a public key with the private half available
     * via ssh-agent(1). An additional signature namespace, used to prevent
     * signature confusion across different domains of use (e.g. file signing
     * vs. email signing) must be provided via the -n flag. Namespaces are
     * arbitrary strings, and may include: “file” for file signing, “email” for
     * email signing. For custom uses, it is recommended to use names following
     * a NAMESPACE@YOUR.DOMAIN pattern to generate unambiguous namespaces.
     *
     * verify:
     * Request to verify a signature generated using ssh-keygen -Y sign as
     * described above. When verifying a signature, ssh-keygen accepts a message
     * on standard input and a signature namespace using -n. A file containing
     * the corresponding signature must also be supplied using the -s flag,
     * along with the identity of the signer using -I and a list of allowed
     * signers via the -f flag. The format of the allowed signers file is
     * documented in the ALLOWED SIGNERS section below. A file containing
     * revoked keys can be passed using the -r flag. The revocation file may be
     * a KRL or a one-per-line list of public keys. Successful verification by
     * an authorized signer is signalled by ssh-keygen returning a zero
     * exit status.
     *
     * @see https://man.openbsd.org/ssh-keygen.1#Y
     * @see https://man.openbsd.org/ssh-keygen.1#ALLOWED_SIGNERS
     */
    public const PARAM_CHECK_PRINCIPALS = '-Y';

    /**
     * Specifies the cipher to use for encryption when writing an OpenSSH-format
     * private key file. The list of available ciphers may be obtained using
     * "ssh -Q cipher". The default is “aes256-ctr”.
     *
     * @see https://man.openbsd.org/ssh-keygen.1#Z
     */
    public const PARAM_CIPHER = '-Z';

    /**
     * Provides a new comment.
     *
     * @see https://man.openbsd.org/ssh-keygen.1#C
     */
    public const PARAM_COMMENT = '-C';

    /**
     * Specifies the hash algorithm used when displaying key fingerprints.
     * Valid options are: “md5” and “sha256”. The default is “sha256”.
     *
     * @see https://man.openbsd.org/ssh-keygen.1#E
     */
    public const PARAM_DISPLAY_FINGERPRINT_HASH = '-E';

    /**
     * This option will read a private or public OpenSSH key file and print to
     * stdout a public key in one of the formats specified by the -m option.
     * The default export format is “RFC4716”. This option allows exporting
     * OpenSSH keys for use by other programs, including several commercial
     * SSH implementations.
     *
     * @see https://man.openbsd.org/ssh-keygen.1#e
     */
    public const PARAM_EXPORT = '-e';

    /**
     * Specifies the filename of the key file.
     *
     * @see https://man.openbsd.org/ssh-keygen.1#f
     */
    public const PARAM_FILENAME = '-f';

    /**
     * Search for the specified hostname (with optional port number) in a
     * known_hosts file, listing any occurrences found. This option is useful to
     * find hashed host names or addresses and may also be used in conjunction
     * with the -H option to print found keys in a hashed format.
     *
     * @see https://man.openbsd.org/ssh-keygen.1#F
     */
    public const PARAM_FIND_HOSTNAME = '-F';

    /**
     * Find the principal(s) associated with the public key of a signature,
     * provided using the -s flag in an authorized signers file provided using
     * the -f flag. The format of the allowed signers file is documented in the
     * ALLOWED SIGNERS section below. If one or more matching principals are
     * found, they are returned on standard output.
     *
     * @see https://man.openbsd.org/ssh-keygen.1#q
     * @see https://man.openbsd.org/ssh-keygen.1#ALLOWED_SIGNERS
     */
    public const PARAM_FIND_PRINCIPALS = '-Y';

    /**
     * For each of the key types (rsa, dsa, ecdsa and ed25519) for which host
     * keys do not exist, generate the host keys with the default key file path,
     * an empty passphrase, default bits for the key type, and default comment.
     * If -f has also been specified, its argument is used as a prefix to the
     * default path for the resulting host key files. This is used by /etc/rc to
     * generate new host keys.
     *
     * @see https://man.openbsd.org/ssh-keygen.1#A
     */
    public const PARAM_GENERATE_ALL = '-A';

    /**
     * Use generic DNS format when printing fingerprint resource records using
     * the -r command.
     *
     * @see https://man.openbsd.org/ssh-keygen.1#g
     */
    public const PARAM_GENERIC_DNS_FORMAT = '-g';

    /**
     * Hash a known_hosts file. This replaces all hostnames and addresses with
     * hashed representations within the specified file; the original content is
     * moved to a file with a .old suffix. These hashes may be used normally by
     * ssh and sshd, but they do not reveal identifying information should the
     * file's contents be disclosed. This option will not modify existing hashed
     * hostnames and is therefore safe to use on files that mix hashed and
     * non-hashed names.
     *
     * @see https://man.openbsd.org/ssh-keygen.1#H
     */
    public const PARAM_HASH_KNOWN_HOSTS = '-H';

    /**
     * When signing a key, create a host certificate instead of a user
     * certificate. Please see the CERTIFICATES section for details.
     *
     * @see https://man.openbsd.org/ssh-keygen.1#h
     * @see https://man.openbsd.org/ssh-keygen.1#CERTIFICATES
     */
    public const PARAM_HOST_CERTIFICATE = '-h';

    /**
     * This option will read an unencrypted private (or public) key file in the
     * format specified by the -m option and print an OpenSSH compatible private
     * (or public) key to stdout. This option allows importing keys from other
     * software, including several commercial SSH implementations.
     * The default import format is “RFC4716”.
     *
     * @see https://man.openbsd.org/ssh-keygen.1#i
     */
    public const PARAM_IDENTITY_FILE = '-i';

    /**
     * Specify a key format for key generation, the -i (import), -e (export)
     * conversion options, and the -p change passphrase operation. The latter
     * may be used to convert between OpenSSH private key and PEM private key
     * formats. The supported key formats are: “RFC4716” (RFC 4716/SSH2 public
     * or private key), “PKCS8” (PKCS8 public or private key) or “PEM” (PEM
     * public key). By default OpenSSH will write newly-generated private keys
     * in its own format, but when converting public keys for export the default
     * format is “RFC4716”. Setting a format of “PEM” when generating or
     * updating a supported private key type will cause the key to be stored in
     * the legacy PEM private key format.
     *
     * @see https://man.openbsd.org/ssh-keygen.1#m
     */
    public const PARAM_KEY_FORMAT = '-m';

    /**
     * Generate a KRL file. In this mode, ssh-keygen will generate a KRL file at
     * the location specified via the -f flag that revokes every key or
     * certificate presented on the command line. Keys/certificates to be
     * revoked may be specified by public key file or using the format described
     * in the KEY REVOCATION LISTS section.
     *
     * @see https://man.openbsd.org/ssh-keygen.1#k
     * @see https://man.openbsd.org/ssh-keygen.1#KEY_REVOCATION_LISTS
     */
    public const PARAM_KRL_FILE = '-k';

    /**
     * Generate candidate Diffie-Hellman Group Exchange (DH-GEX) parameters for
     * eventual use by the ‘diffie-hellman-group-exchange-*’ key exchange
     * methods. The numbers generated by this operation must be further screened
     * before use. See the MODULI GENERATION section for more information.
     *
     * @see https://man.openbsd.org/ssh-keygen.1#M
     * @see https://man.openbsd.org/ssh-keygen.1#MODULI_GENERATION
     */
    public const PARAM_MODULI = '-M';

    /**
     * Provides the new passphrase.
     *
     * @see https://man.openbsd.org/ssh-keygen.1#N
     */
    public const PARAM_NEW_PASSPHRASE = '-N';

    /**
     * Specify a key/value option. These are specific to the operation that
     * ssh-keygen has been requested to perform.
     * When signing certificates, one of the options listed in the CERTIFICATES
     * section may be specified here.
     *
     * When performing moduli generation or screening, one of the options listed
     * in the MODULI GENERATION section may be specified.
     *
     * When generating a key that will be hosted on a FIDO authenticator, this
     * flag may be used to specify key-specific options.
     *
     * @see https://man.openbsd.org/ssh-keygen.1#O
     * @see https://man.openbsd.org/ssh-keygen.1#CERTIFICATES
     * @see https://man.openbsd.org/ssh-keygen.1#MODULI_GENERATION
     */
    public const PARAM_OPTION = '-O';

    /**
     * Provides the (old) passphrase.
     *
     * @see https://man.openbsd.org/ssh-keygen.1#P
     */
    public const PARAM_PASSPHRASE = '-P';

    /**
     * Download the public keys provided by the PKCS#11 shared library pkcs11.
     * When used in combination with -s, this option indicates that a CA key
     * resides in a PKCS#11 token (see the CERTIFICATES section for details).
     *
     * @see https://man.openbsd.org/ssh-keygen.1#D
     */
    public const PARAM_PKCS11 = '-D';

    /**
     * Specify one or more principals (user or host names) to be included in a
     * certificate when signing a key. Multiple principals may be specified,
     * separated by commas. Please see the CERTIFICATES section for details.
     *
     * @see https://man.openbsd.org/ssh-keygen.1#n
     * @see https://man.openbsd.org/ssh-keygen.1#CERTIFICATES
     */
    public const PARAM_PRINCIPALS = '-n';

    /**
     * Prints the contents of one or more certificates.
     *
     * @see https://man.openbsd.org/ssh-keygen.1#L
     */
    public const PARAM_PRINT_CERTIFICATE_CONTENT = '-L';

    /**
     * Specifies a path to a library that will be used when creating FIDO
     * authenticator-hosted keys, overriding the default of using the internal
     * USB HID support.
     *
     * @see https://man.openbsd.org/ssh-keygen.1#w
     */
    public const PARAM_PROVIDER = '-w';

    /**
     * This option will read a private OpenSSH format file and print an OpenSSH
     * public key to stdout.
     *
     * @see https://man.openbsd.org/ssh-keygen.1#y
     */
    public const PARAM_PUBLIC_FROM_PRIVATE = '-y';

    /**
     * Silence ssh-keygen.
     *
     * @see https://man.openbsd.org/ssh-keygen.1#q
     */
    public const PARAM_QUIET = '-q';

    /**
     * Removes all keys belonging to the specified hostname (with optional port
     * number) from a known_hosts file. This option is useful to delete hashed
     * hosts (see the -H option above).
     *
     * @see https://man.openbsd.org/ssh-keygen.1#R
     */
    public const PARAM_REMOVE_KNOWN_HOST = '-R';

    /**
     * Requests changing the comment in the private and public key files.
     * The program will prompt for the file containing the private keys, for the
     * passphrase if the key has one, and for the new comment.
     *
     * @see https://man.openbsd.org/ssh-keygen.1#c
     */
    public const PARAM_REQUEST_COMMENT_CHANGE = '-c';

    /**
     * Download resident keys from a FIDO authenticator. Public and private key
     * files will be written to the current directory for each downloaded key.
     * If multiple FIDO authenticators are attached, keys will be downloaded
     * from the first touched authenticator.
     *
     * @see https://man.openbsd.org/ssh-keygen.1#K
     */
    public const PARAM_RESIDENT_KEYS = '-K';

    /**
     * When saving a private key, this option specifies the number of KDF (key
     * derivation function, currently bcrypt_pbkdf(3)) rounds used. Higher
     * numbers result in slower passphrase verification and increased resistance
     * to brute-force password cracking (should the keys be stolen).
     * The default is 16 rounds.
     *
     * @see https://man.openbsd.org/ssh-keygen.1#a
     */
    public const PARAM_ROUNDS = '-a';

    /**
     * Specifies a serial number to be embedded in the certificate to
     * distinguish this certificate from others from the same CA. If the
     * serial_number is prefixed with a ‘+’ character, then the serial number
     * will be incremented for each certificate signed on a single command-line.
     * The default serial number is zero.
     * When generating a KRL, the -z flag is used to specify a KRL
     * version number.
     *
     * @see https://man.openbsd.org/ssh-keygen.1#z
     */
    public const PARAM_SERIAL_NUMBER = '-z';

    /**
     * Show fingerprint of specified public key file. For RSA and DSA keys
     * ssh-keygen tries to find the matching public key file and prints its
     * fingerprint. If combined with -v, a visual ASCII art representation of
     * the key is supplied with the fingerprint.
     *
     * @see https://man.openbsd.org/ssh-keygen.1#l
     */
    public const PARAM_SHOW_FINGERPRINT = '-l';

    /**
     * Print the SSHFP fingerprint resource record named hostname for the
     * specified public key file.
     *
     * @see https://man.openbsd.org/ssh-keygen.1#r
     */
    public const PARAM_SSHFP_RECORD = '-r';

    /**
     * Specifies the type of key to create. The possible values are “dsa”,
     * “ecdsa”, “ecdsa-sk”, “ed25519”, “ed25519-sk”, or “rsa”.
     * This flag may also be used to specify the desired signature type when
     * signing certificates using an RSA CA key. The available RSA signature
     * variants are “ssh-rsa” (SHA1 signatures, not recommended),
     * “rsa-sha2-256”, and “rsa-sha2-512” (the default).
     *
     * @see https://man.openbsd.org/ssh-keygen.1#t
     */
    public const PARAM_TYPE = '-t';

    /**
     * Update a KRL. When specified with -k, keys listed via the command line
     * are added to the existing KRL rather than a new KRL being created.
     *
     * @see https://man.openbsd.org/ssh-keygen.1#u
     */
    public const PARAM_UPDATE_KRL = '-u';

    /**
     * Specify a validity interval when signing a certificate. A validity
     * interval may consist of a single time, indicating that the certificate is
     * valid beginning now and expiring at that time, or may consist of two
     * times separated by a colon to indicate an explicit time interval.
     * The start time may be specified as the string “always” to indicate the
     * certificate has no specified start time, a date in YYYYMMDD format, a
     * time in YYYYMMDDHHMM[SS] format, a relative time (to the current time)
     * consisting of a minus sign followed by an interval in the format
     * described in the TIME FORMATS section of sshd_config(5).
     *
     * The end time may be specified as a YYYYMMDD date, a YYYYMMDDHHMM[SS]
     * time, a relative time starting with a plus character or the string
     * “forever” to indicate that the certificate has no expiry date.
     *
     * For example: “+52w1d” (valid from now to 52 weeks and one day from now),
     * “-4w:+4w” (valid from four weeks ago to four weeks from now),
     * “20100101123000:20110101123000” (valid from 12:30 PM, January 1st, 2010
     * to 12:30 PM, January 1st, 2011), “-1d:20110101” (valid from yesterday to
     * midnight, January 1st, 2011), “-1m:forever” (valid from one minute ago
     * and never expiring).
     *
     * @see https://man.openbsd.org/ssh-keygen.1#V
     */
    public const PARAM_VALIDITY_INTERVAL = '-V';

    /**
     * Verbose mode. Causes ssh-keygen to print debugging messages about its
     * progress. This is helpful for debugging moduli generation. Multiple -v
     * options increase the verbosity. The maximum is 3.
     *
     * @see https://man.openbsd.org/ssh-keygen.1#v
     */
    public const PARAM_VERBOSE = '-v';

    public const RSA_VARIANT_RSA = 'ssh-rsa';

    public const RSA_VARIANT_RSA2_256 = 'ssh-rsa2-256';

    public const RSA_VARIANT_RSA2_512 = 'ssh-rsa2-512';

    public const TYPE_DSA = 'dsa';

    public const TYPE_ECDSA = 'ecdsa';

    public const TYPE_ECDSA_SK = 'ecdsa-sk';

    public const TYPE_ED25519 = 'ed25519';

    public const TYPE_ED25519_SK = 'ed25519-sk';

    public const TYPE_RSA = 'rsa';

    public function __construct(
        private Filesystem $filesystem,
        private string $signatureStagingPath
    ) {
    }

    /**
     * @param string $certificate
     *
     * @return string
     * @throws LogicException
     * @throws ProcessSignaledException
     * @throws ProcessTimedOutException
     * @throws RuntimeException
     * @throws \Symfony\Component\Process\Exception\RuntimeException
     */
    public function extractFingerprint(string $certificate): string
    {
        $process = $this
            ->createProcess([
                self::PARAM_SHOW_FINGERPRINT,
                '-f' => '-',
            ])
            ->setInput($certificate);
        $output = $this->executeProcess($process)->getOutput();

        return trim(preg_replace(
            '/^.+ .+:(\S+) .+$/',
            '$1',
            $output
        ));
    }

    /**
     * @param string $mode
     * @param string $privateKeyPath
     *
     * @return string
     * @throws LogicException
     * @throws ProcessSignaledException
     * @throws ProcessTimedOutException
     * @throws RuntimeException
     * @throws \Symfony\Component\Process\Exception\RuntimeException
     */
    public function generateCaCertificate(
        string $mode,
        string $privateKeyPath
    ): string {
        assert(in_array($mode, [
            self::CA_MODE_USER,
            self::CA_MODE_HOST,
        ]));

        $process = $this->createProcess([
            self::PARAM_COMMENT => ucfirst($mode) . ' CA',
            #self::PARAM_KEY_FORMAT => 'PEM',
            self::PARAM_TYPE => self::TYPE_ED25519,
            self::PARAM_NEW_PASSPHRASE => '',
            self::PARAM_QUIET,
            self::PARAM_FILENAME => $privateKeyPath,
        ]);
        $this->executeProcess($process);

        $publicKeyPath = $this->inferPublicKeyPath($privateKeyPath);
        $publicKey = file_get_contents($publicKeyPath);

        if ($publicKey === false) {
            throw new RuntimeException(
                'Failed to generate CA key pair'
            );
        }

        return $publicKey;
    }

    /**
     * @param string $certificate
     *
     * @return string
     * @throws IOException
     * @throws LogicException
     * @throws ProcessSignaledException
     * @throws ProcessTimedOutException
     * @throws RuntimeException
     * @throws \Symfony\Component\Process\Exception\RuntimeException
     */
    public function renderVisualFingerprint(string $certificate): string
    {
        $process = $this
            ->createProcess([
                self::PARAM_SHOW_FINGERPRINT,
                self::PARAM_VERBOSE,
                '-f' => '-',
            ])
            ->setInput($certificate);

        $output = $this->executeProcess($process)->getOutput();
        $lines = explode(PHP_EOL, $output);
        $data = [];

        foreach (array_slice($lines, 2, 9) as $line) {
            $characters = trim($line, '|');

            $data[] = $characters;
        }

        return implode(PHP_EOL, $data);
    }

    /**
     * @param string                 $caKeyPath
     * @param string                 $publicKey
     * @param string                 $identity
     * @param array                  $principals
     * @param int                    $serial
     * @param DateTimeImmutable|null $validFrom
     * @param DateTimeImmutable|null $validUntil
     *
     * @return string
     * @throws IOException
     * @throws InvalidArgumentException
     * @throws LogicException
     * @throws ProcessSignaledException
     * @throws ProcessTimedOutException
     * @throws RuntimeException
     * @throws \Symfony\Component\Process\Exception\RuntimeException
     */
    public function signHostKey(
        string $caKeyPath,
        string $publicKey,
        string $identity,
        array $principals,
        int $serial,
        DateTimeImmutable|null $validFrom = null,
        DateTimeImmutable|null $validUntil = null,
    ): string {
        if (count($principals) > self::MAX_PRINCIPALS) {
            throw new InvalidArgumentException(
                'Too many certificate principals specified'
            );
        }

        $publicKeyPath = $this->writeTemporaryFile(
            $publicKey,
            "{$identity}_host_staging_"
        );

        $validitySpecification = $this->buildValiditySpecification(
            $validFrom,
            $validUntil
        );

        $process = $this->createProcess([
            self::PARAM_CA_KEY => $caKeyPath,
            self::PARAM_CERTIFICATE_IDENTITY => $identity,
            self::PARAM_PRINCIPALS => $principals,
            self::PARAM_VALIDITY_INTERVAL => $validitySpecification,
            self::PARAM_SERIAL_NUMBER => $serial,
            self::PARAM_HOST_CERTIFICATE,
            self::PARAM_QUIET,
            $publicKeyPath,
        ]);

        $this->executeProcess($process);

        $certificatePath = $this->inferCertificatePath($publicKeyPath);
        $certificate = file_get_contents($certificatePath);

        if ($certificate === false) {
            throw new RuntimeException('Failed to read certificate');
        }

        $this->filesystem->remove([$publicKeyPath, $certificatePath]);

        return $certificate;
    }

    /**
     * @param string                 $caKeyPath
     * @param string                 $publicKey
     * @param string                 $identity
     * @param array                  $principals
     * @param int                    $serial
     * @param DateTimeImmutable|null $validFrom
     * @param DateTimeImmutable|null $validUntil
     * @param array|null             $options
     *
     * @return string
     * @throws IOException
     * @throws InvalidArgumentException
     * @throws LogicException
     * @throws ProcessSignaledException
     * @throws ProcessTimedOutException
     * @throws RuntimeException
     * @throws \Symfony\Component\Process\Exception\RuntimeException
     */
    public function signUserKey(
        string $caKeyPath,
        string $publicKey,
        string $identity,
        array $principals,
        int $serial,
        DateTimeImmutable|null $validFrom = null,
        DateTimeImmutable|null $validUntil = null,
        array|null $options = null,
    ): string {
        if (count($principals) > self::MAX_PRINCIPALS) {
            throw new InvalidArgumentException(
                'Too many certificate principals specified'
            );
        }

        $publicKeyPath = $this->writeTemporaryFile(
            $publicKey,
            "{$identity}_user_staging_"
        );

        $validitySpecification = $this->buildValiditySpecification(
            $validFrom,
            $validUntil
        );

        $parsedOptions = $this->buildOptions($options);

        $process = $this->createProcess([
            self::PARAM_CA_KEY => $caKeyPath,
            self::PARAM_CERTIFICATE_IDENTITY => $identity,
            self::PARAM_PRINCIPALS => $principals,
            self::PARAM_VALIDITY_INTERVAL => $validitySpecification,
            self::PARAM_SERIAL_NUMBER => $serial,
            self::PARAM_OPTION => $parsedOptions,
            self::PARAM_QUIET,
            $publicKeyPath,
        ]);

        $this->executeProcess($process);

        $certificatePath = $this->inferCertificatePath($publicKeyPath);
        $certificate = file_get_contents($certificatePath);

        if ($certificate === false) {
            throw new RuntimeException('Failed to read certificate');
        }

        $this->filesystem->remove([$publicKeyPath, $certificatePath]);

        return $certificate;
    }

    /**
     * @throws RuntimeException
     * @throws LogicException
     * @throws ProcessSignaledException
     * @throws ProcessTimedOutException
     * @throws \Symfony\Component\Process\Exception\RuntimeException
     */
    private function assertSshKeygenIsCallable(): void
    {
        $process = new Process(['which', 'ssh-keygen']);
        $process->run();

        if ( ! $process->isSuccessful()) {
            throw new RuntimeException(
                'Failed to locate ssh-keygen. Make sure it is ' .
                'available on the system and included in $PATH.'
            );
        }
    }

    /**
     * @param array<string, string[]|string|null> $arguments
     *
     * @return string[]
     */
    private function buildArguments(array $arguments): array
    {
        $list = [];

        foreach ($arguments as $key => $values) {
            $values = (array)$values;

            foreach ($values as $value) {
                if ($value === false) {
                    continue;
                }

                if ( ! is_numeric($key)) {
                    $list[] = $key;
                }

                if ($value !== true) {
                    $list[] = (string)$value;
                }
            }
        }

        return $list;
    }

    private function buildOptions(array|null $options): array
    {
        if ($options === null) {
            return [];
        }

        $flat = [];

        foreach ($options as $name => $content) {
            if ($content === false) {
                continue;
            }

            if (is_array($content)) {
                $content = implode(',', array_map(
                    'trim',
                    $content
                ));
            }

            if (is_string($content)) {
                $content = preg_replace(
                    '/(\s)/',
                    '\\\$1',
                    $content
                );
            }

            $flat[] = $content !== true ? "{$name}={$content}" : $name;
        }

        return $flat;
    }

    /**
     * @param DateTimeImmutable|null $validFrom
     * @param DateTimeImmutable|null $validUntil
     *
     * @return string
     */
    private function buildValiditySpecification(
        DateTimeImmutable|null $validFrom,
        DateTimeImmutable|null $validUntil,
    ): string {
        $validFromString = $validFrom
            ? $validFrom->format('YmdHis')
            : 'always';
        $validUntilString = $validUntil
            ? $validUntil->format('YmdHis')
            : 'forever';

        return "{$validFromString}:{$validUntilString}";
    }

    /**
     * @param array $arguments
     *
     * @return Process
     * @throws LogicException
     */
    private function createProcess(array $arguments): Process
    {
        return new Process([
            self::EXECUTABLE,
            ...$this->buildArguments($arguments),
        ]);
    }

    /**
     * @param Process $process
     *
     * @return Process
     * @throws LogicException
     * @throws ProcessSignaledException
     * @throws ProcessTimedOutException
     * @throws RuntimeException
     * @throws \Symfony\Component\Process\Exception\RuntimeException
     */
    private function executeProcess(Process $process): Process
    {
        $this->assertSshKeygenIsCallable();

        $process->run();

        if ( ! $process->isSuccessful()) {
            throw new RuntimeException(
                'ssh-keygen reported an error: ' .
                $process->getErrorOutput()
            );
        }

        return $process;
    }

    private function inferCertificatePath(string $publicKeyPath): string
    {
        $basePath = Path::getDirectory($publicKeyPath);
        $baseName = Path::getFilenameWithoutExtension($publicKeyPath);

        return "$basePath/{$baseName}-cert.pub";
    }

    private function inferPublicKeyPath(string $privateKeyPath): string
    {
        $basePath = Path::getDirectory($privateKeyPath);
        $baseName = Path::getFilenameWithoutExtension($privateKeyPath);

        return "$basePath/{$baseName}.pub";
    }

    /**
     * @param string $content
     * @param string $prefix
     *
     * @return string
     * @throws IOException
     */
    private function writeTemporaryFile(
        string $content,
        string $prefix
    ): string {
        $temporaryPath = $this->filesystem->tempnam(
            $this->signatureStagingPath,
            $prefix,
            '.tmp',
        );

        $this->filesystem->dumpFile(
            $temporaryPath,
            $content
        );

        return $temporaryPath;
    }
}
