<?php

namespace Repositories;

use Doctrine\ORM\EntityRepository;

use Entities\{
    Layer2Address as Layer2AddressEntity,
    Router as RouterEntity,
    Vlan as VlanEntity,
    VlanInterface as VlanInterfaceEntity
};

/**
 * VlanInterface
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class VlanInterface extends EntityRepository
{

    /**
     * Utility function to provide an array of all VLAN interfaces on a given
     * VLAN for a given protocol.
     *
     * Returns an array of elements such as:
     *
     *     [
     *         [cid] => 999
     *         [cname] => Customer Name
     *         [abrevcname] => Abbreviated Customer Name
     *         [cshortname] => shortname
     *         [autsys] => 65500
     *         [gmaxprefixes] => 20        // from cust table (global)
     *         [peeringmacro] => ABC
     *         [peeringmacrov6] => ABC
     *         [vid]        => 2
     *         [vtag]       => 10,
     *         [vname]      => "Peering LAN #1
     *         [viid] => 120
     *         [vliid] => 159
     *         [canping] => 1
     *         [enabled] => 1              // VLAN interface enabled for requested protocol?
     *         [address] => 192.0.2.123    // assigned address for requested protocol?
     *         [monitorrcbgp] => 1
     *         [bgpmd5secret] => qwertyui  // MD5 for requested protocol
     *         [hostname] => hostname      // Hostname
     *         [maxbgpprefix] => 20        // VLAN interface max prefixes
     *         [as112client] => 1          // if the member is an as112 client or not
     *         [rsclient] => 1             // if the member is a route server client or not
     *         [busyhost]
     *         [sid]
     *         [sname]
     *         [cabid]
     *         [cabname]
     *         [location_name]
     *         [location_tag]
     *         [location_shortname]
     *     ]
     *
     * @param \Entities\Vlan $vlan The VLAN
     * @param int $proto Either 4 or 6
     * @param bool $useResultCache If true, use Doctrine's result cache (ttl set to one hour)
     * @param int $pistatus The status of the physical interface
     * @return array As defined above.
     * @throws \IXP_Exception On bad / no protocol
     */
    public function getForProto( $vlan, $proto, $useResultCache = true, $pistatus = \Entities\PhysicalInterface::STATUS_CONNECTED )
    {
        if( !in_array( $proto, [ 4, 6 ] ) )
            throw new \IXP_Exception( 'Invalid protocol specified' );


        $qstr = "SELECT c.id              AS cid, 
                        c.name            AS cname, 
                        c.abbreviatedName AS abrevcname, 
                        c.shortname       AS cshortname, 
                        c.autsys          AS autsys, 
                        c.maxprefixes     AS gmaxprefixes, 
                        c.peeringmacro    AS peeringmacro, 
                        c.peeringmacrov6  AS peeringmacrov6,
                        
                        v.id                 AS vid,
                        v.number             AS vtag,
                        v.name               AS vname,
                        vi.id                AS viid, 

                        vli.id AS vliid, 
                       
                        vli.ipv{$proto}enabled      AS enabled, 
                        vli.ipv{$proto}hostname     AS hostname, 
                        vli.ipv{$proto}monitorrcbgp AS monitorrcbgp, 
                        vli.ipv{$proto}bgpmd5secret AS bgpmd5secret, 
                        vli.maxbgpprefix            AS maxbgpprefix,
                        vli.as112client             AS as112client,
                        vli.rsclient                AS rsclient, 
                        vli.busyhost                AS busyhost, 
                        vli.irrdbfilter             AS irrdbfilter,
                        vli.ipv{$proto}canping      AS canping,
                        
                        addr.address AS address,
                       
                        s.id   AS sid,
                        s.name AS sname,
                       
                        cab.id   AS cabid,
                        cab.name AS cabname,
                       
                        l.name      AS location_name, 
                        l.shortname AS location_shortname, 
                        l.tag       AS location_tag
                       
                    FROM Entities\\VlanInterface vli
                        LEFT JOIN vli.VirtualInterface vi
                        LEFT JOIN vli.IPv{$proto}Address addr
                        LEFT JOIN vi.Customer c
                        LEFT JOIN vi.PhysicalInterfaces pi
                        LEFT JOIN pi.SwitchPort sp
                        LEFT JOIN sp.Switcher s
                        LEFT JOIN s.Cabinet cab
                        LEFT JOIN cab.Location l
                        LEFT JOIN vli.Vlan v
                    WHERE
                        v = :vlan
                        AND " . Customer::DQL_CUST_ACTIVE     . "
                        AND " . Customer::DQL_CUST_CURRENT    . "
                        AND " . Customer::DQL_CUST_TRAFFICING . "
                        AND pi.status = :pistatus
                        
                    GROUP BY 
                        vli.id, c.id, c.name, c.abbreviatedName, c.shortname, c.autsys,
                        c.maxprefixes, c.peeringmacro, c.peeringmacrov6,
                        vli.ipv{$proto}enabled, addr.address, vli.ipv{$proto}bgpmd5secret, vli.maxbgpprefix,
                        vli.ipv{$proto}hostname, vli.ipv{$proto}monitorrcbgp, vli.busyhost,
                        vli.as112client, vli.rsclient, vli.irrdbfilter, vli.ipv{$proto}canping,
                        s.id, s.name,
                        cab.id, cab.name,
                        l.name, l.shortname, l.tag
                        ";

        $qstr .= " ORDER BY c.autsys ASC, vli.id ASC";

        $q = $this->getEntityManager()->createQuery( $qstr );
        $q->setParameter( 'vlan', $vlan );
        $q->setParameter( 'pistatus', $pistatus );
        $q->useResultCache( $useResultCache, 3600 );
        return $q->getArrayResult();
    }


    /**
     * Utility function to provide an array of all VLAN interfaces on a given IXP.
     *
     * Returns an array of elements such as:
     *
     *     [
     *         [cid] => 999
     *         [cname] => Customer Name
     *         [cshortname] => shortname
     *         [autsys] => 65500
     *         [vliid] => 159
     *
     *         [ipv4enabled]                   // VLAN interface enabled
     *         [ipv4canping]                   // Can ping for moniroting
     *         [ipv4hostname]                  // hostname
     *         [ipv4monitorrcbgp]              // Can monitor RC BGP session
     *         [ipv4address] => 192.0.2.123    // assigned address
     *         [ipv4bgpmd5secret] => qwertyui  // MD5
     *
     *         [ipv6enabled]                   // VLAN interface enabled
     *         [ipv6canping]                   // Can ping for moniroting
     *         [ipv6hostname]                  // hostname
     *         [ipv6monitorrcbgp]              // Can monitor RC BGP session
     *         [ipv6address] => 192.0.2.123    // assigned address
     *         [ipv6bgpmd5secret] => qwertyui  // MD5
     *
     *         [maxbgpprefix] => 20        // VLAN interface max prefixes
     *         [as112client] => 1          // if the member is an as112 client or not
     *         [rsclient] => 1             // if the member is a route server client or not
     *     ]
     *
     * @param \Entities\Vlan $vlan The VLAN
     * @param int $proto Either 4 or 6
     * @param bool $useResultCache If true, use Doctrine's result cache (ttl set to one hour)
     * @return array As defined above.
     * @throws \IXP_Exception On bad / no protocol
     */
    public function getForIXP( $ixp, $useResultCache = true )
    {
        $qstr = "SELECT c.id AS cid, c.name AS cname, c.shortname AS cshortname, c.autsys AS autsys,

                    vli.id AS vliid,

                    vli.ipv4enabled      AS ipv4enabled,
                    vli.ipv4hostname     AS ipv4hostname,
                    vli.ipv4canping      AS ipv4canping,
                    vli.ipv4monitorrcbgp AS ipv4monitorrcbgp,
                    vli.ipv4bgpmd5secret AS ipv4bgpmd5secret,
                    v4addr.address       AS ipv4address,

                    vli.ipv6enabled      AS ipv6enabled,
                    vli.ipv6hostname     AS ipv6hostname,
                    vli.ipv6canping      AS ipv6canping,
                    vli.ipv6monitorrcbgp AS ipv6monitorrcbgp,
                    vli.ipv6bgpmd5secret AS ipv6bgpmd5secret,
                    v6addr.address       AS ipv6address,

                    vli.maxbgpprefix AS maxbgpprefix,
                    vli.as112client AS as112client,
                    vli.rsclient AS rsclient,

                    s.name AS switchname,
                    sp.name AS switchport,

                    v.number AS vlannumber,

                    ixp.shortname AS ixpname

        FROM Entities\\VlanInterface vli
            JOIN vli.VirtualInterface vi
            JOIN vli.IPv4Address v4addr
            JOIN vli.IPv6Address v6addr
            JOIN vi.Customer c
            JOIN vi.PhysicalInterfaces pi
            JOIN pi.SwitchPort sp
            JOIN sp.Switcher s
            JOIN vli.Vlan v
            JOIN v.Infrastructure inf
            JOIN inf.IXP ixp

        WHERE
            ixp = :ixp
            AND " . Customer::DQL_CUST_ACTIVE     . "
            AND " . Customer::DQL_CUST_CURRENT    . "
            AND " . Customer::DQL_CUST_TRAFFICING . "
            AND pi.status = " . \Entities\PhysicalInterface::STATUS_CONNECTED;

        $qstr .= " ORDER BY c.shortname ASC, vli.id ASC";

        $q = $this->getEntityManager()->createQuery( $qstr );

        $q->setParameter( 'ixp', $ixp );
        $q->useResultCache( $useResultCache, 3600 );
        return $q->getArrayResult();
    }

    /**
     * Utility function to provide an array of all VLAN interfaces on a given VLAN.
     *
     * Returns an array where each element has the following format:
     *
     *      [
     *        "cid" => 69,
     *        "cname" => "ABC Ltd",
     *        "caname" => "ABC Ltd",
     *        "csname" => "abc",
     *        "cautsys" => 65501,
     *        "vid" => 2,
     *        "vtag" => 10,
     *        "viid" => 33,
     *        "vliid" => 100,
     *        "ipv4enabled" => true,
     *        "ipv4hostname" => "abc.inex.ie",
     *        "ipv4canping" => true,
     *        "ipv4monitorrcbgp" => true,
     *        "ipv4bgpmd5secret" => "secret,
     *        "ipv4address" => "192.0.2.58",
     *        "ipv6enabled" => false,
     *        "ipv6hostname" => "",
     *        "ipv6canping" => false,
     *        "ipv6monitorrcbgp" => true,
     *        "ipv6bgpmd5secret" => "",
     *        "ipv6address" => null,
     *        "busyhost" => false,
     *        "as112client" => true,
     *        "rsclient" => true,
     *        "sid" => 54,
     *        "sname" => "swi1-cwt1-1",
     *        "cabid" => 4,
     *        "cabname" => "INEX-CWT1-1",
     *        "locid" => 2,
     *        "locname" => "Equinix DB1 (Citywest)",
     *        "locsname" => "EQX-DB1",
     *      ]
     *
     * $param int  $vlan            VLAN ID
     * @param bool $externalOnly    If true then only external (non-internal) interfaces will be returned
     * @param int  $pistatus        The status that at least one associated physical interface must match.
     *                              The default value will only pull VLAN interfaces that have a connected interface.
     *                              This is probably what you want.
     * @param bool $useResultCache  If true, use Doctrine's result cache to prevent needless database overhead.
     * @return array As defined above.
     */
    public function getForVlan( int $vlan, bool $externalOnly = false, int $pistatus = \Entities\PhysicalInterface::STATUS_CONNECTED, bool $useResultCache = true ): array
    {
        $qstr = "SELECT DISTINCT vli.id      AS vliid,

                        c.id                 AS cid, 
                        c.name               AS cname,
                        c.abbreviatedName    AS caname,
                        c.shortname          AS csname,
                        c.autsys             AS cautsys,
                        
                        v.id                 AS vid,
                        v.number             AS vtag,
                        v.name               AS vname,

                        vi.id                AS viid, 
                        
                        vli.ipv4enabled      AS ipv4enabled,
                        vli.ipv4hostname     AS ipv4hostname,
                        vli.ipv4canping      AS ipv4canping,
                        vli.ipv4monitorrcbgp AS ipv4monitorrcbgp,
                        vli.ipv4bgpmd5secret AS ipv4bgpmd5secret,
                        v4addr.address       AS ipv4address,

                        vli.ipv6enabled      AS ipv6enabled,
                        vli.ipv6hostname     AS ipv6hostname,
                        vli.ipv6canping      AS ipv6canping,
                        vli.ipv6monitorrcbgp AS ipv6monitorrcbgp,
                        vli.ipv6bgpmd5secret AS ipv6bgpmd5secret,
                        v6addr.address       AS ipv6address,

                        vli.busyhost         AS busyhost,
                        vli.as112client      AS as112client,
                        vli.rsclient         AS rsclient,

                        s.id                 AS sid,
                        s.name               AS sname,
                        
                        cab.id               AS cabid,
                        cab.name             AS cabname,
                        
                        loc.id               AS locid,
                        loc.name             AS locname,
                        loc.shortname        AS locsname

                    FROM Entities\\VlanInterface vli
                        LEFT JOIN vli.Vlan v
                        LEFT JOIN vli.IPv4Address v4addr
                        LEFT JOIN vli.IPv6Address v6addr
                        LEFT JOIN vli.VirtualInterface vi
                        LEFT JOIN vi.Customer c
                        LEFT JOIN vi.PhysicalInterfaces pi
                        LEFT JOIN pi.SwitchPort sp
                        LEFT JOIN sp.Switcher s
                        LEFT JOIN s.Cabinet cab
                        LEFT JOIN cab.Location loc
                        
                    WHERE
                        v = :vlan
                        AND " . Customer::DQL_CUST_ACTIVE     . "
                        AND " . Customer::DQL_CUST_CURRENT    . "
                        AND " . Customer::DQL_CUST_TRAFFICING . "
                        AND pi.status = :pistatus ";

        if( $externalOnly ) {
            $qstr .= "AND " . Customer::DQL_CUST_EXTERNAL;
        }

        $qstr .= " ORDER BY c.name ASC";

        return $this->getEntityManager()->createQuery( $qstr )
                ->setParameter( 'vlan', $vlan )
                ->setParameter( 'pistatus', $pistatus )
                ->useResultCache( $useResultCache, 3600 )
                ->getScalarResult();
    }

    /**
     * Utility function to provide an array of VLAN interface objects on a given VLAN.
     *
     * @param \Entities\Vlan $vlan The VLAN to gather VlanInterfaces for
     * @param bool $useResultCache If true, use Doctrine's result cache.
     * @return \Entities\VlanInterface[] Indexed by VlanInterface ID
     */
    public function getObjectsForVlan( $vlan, $useResultCache = true, $protocol = null )
    {
        if( in_array( $protocol, [ 4, 6 ] ) ) {
            $pq = " AND vli.ipv{$protocol}enabled = 1";
        } else

        $qstr = "SELECT vli
                    FROM Entities\\VlanInterface vli
                        JOIN vli.Vlan v
                        JOIN vli.VirtualInterface vi
                        JOIN vi.PhysicalInterfaces pi
                        JOIN vi.Customer c

                    WHERE
                        v = :vlan
                        AND " . Customer::DQL_CUST_ACTIVE     . "
                        AND " . Customer::DQL_CUST_CURRENT    . "
                        AND " . Customer::DQL_CUST_TRAFFICING . "
                        AND " . Customer::DQL_CUST_EXTERNAL   . "
                        AND pi.status = " . \Entities\PhysicalInterface::STATUS_CONNECTED . ( $pq ?? '' ) . "

                    ORDER BY c.name ASC";

        $q = $this->getEntityManager()->createQuery( $qstr );
        $q->setParameter( 'vlan', $vlan );
        $q->useResultCache( $useResultCache, 3600 );

        $vlis = [];
        foreach( $q->getResult() as $vli )
            $vlis[ $vli->getId() ] = $vli;

        return $vlis;
    }


    /**
     * Utility function to provide an array of all VLAN interface objects for a given
     * customer at an optionally given IXP.
     *
     * @param \Entities\Customer $customer The customer
     * @param \Entities\IXP      $ixp      The optional IXP
     * @param bool $useResultCache If true, use Doctrine's result cache
     * @return \Entities\VlanInterface[] Index by the VlanInterface ID
     */
    public function getForCustomer( $customer, $ixp = false, $useResultCache = true )
    {
        $qstr = "SELECT vli
                    FROM Entities\\VlanInterface vli
                        JOIN vli.VirtualInterface vi
                        JOIN vi.Customer c
                        JOIN vli.Vlan v";

        if( $ixp )
        {
            $qstr .= " JOIN vi.PhysicalInterfaces pi
                        JOIN pi.SwitchPort sp
                        JOIN sp.Switcher sw
                        JOIN sw.Infrastructure i
                        JOIN i.IXP ixp";
        }

        $qstr .= " WHERE c = :customer";

        if( $ixp )
        {
            $qstr .= " AND ixp = :ixp
                        ORDER BY ixp.id, v.number";
        }
        else
            $qstr .= " ORDER BY v.number";


        $q = $this->getEntityManager()->createQuery( $qstr );
        $q->setParameter( 'customer', $customer );

        if( $ixp )
            $q->setParameter( 'ixp', $ixp );

        $q->useResultCache( $useResultCache, 3600 );

        $vlis = [];

        foreach( $q->getResult() as $vli )
            $vlis[ $vli->getId() ] = $vli;

        return $vlis;
    }


    /**
     * Utility function to get and return active VLAN interfaces on the requested protocol
     * suitable for route collector / server configuration.
     *
     * Sample return:
     *
     *     [
     *         [cid] => 999
     *         [cname] => Customer Name
     *         [cshortname] => shortname
     *         [autsys] => 65000
     *         [peeringmacro] => QWE              // or AS65500 if not defined
     *         [vliid] => 159
     *         [fvliid] => 00159                  // formatted %05d
     *         [address] => 192.0.2.123
     *         [bgpmd5secret] => qwertyui         // or false
     *         [as112client] => 1                 // if the member is an as112 client or not
     *         [rsclient] => 1                    // if the member is a route server client or not
     *         [maxprefixes] => 20
     *         [irrdbfilter] => 0/1               // if IRRDB filtering should be applied
     *         [location_name] => Interxion DUB1
     *         [location_shortname] => IX-DUB1
     *         [location_tag] => ix1
     *     ]
     *
     * @param Vlan $vlan
     * @return array As defined above
     */
    public function sanitiseVlanInterfaces( VlanEntity $vlan, int $protocol = 4, int $target = RouterEntity::TYPE_ROUTE_SERVER, bool $quarantine = false ): array {

        $ints = $this->getForProto( $vlan, $protocol, false,
            $quarantine  ? \Entities\PhysicalInterface::STATUS_QUARANTINE : \Entities\PhysicalInterface::STATUS_CONNECTED
        );

        $newints = [];

        foreach( $ints as $int )
        {
            if( !$int['enabled'] ) {
                continue;
            }

            $int['protocol'] = $protocol;

            // don't need this anymore:
            unset( $int['enabled'] );

            if( $target == RouterEntity::TYPE_ROUTE_SERVER && !$int['rsclient'] ) {
                continue;
            }

            if( $target == RouterEntity::TYPE_AS112 && !$int['as112client'] ) {
                continue;
            }

            $int['fvliid'] = sprintf( '%04d', $int['vliid'] );

            if( $int['maxbgpprefix'] && $int['maxbgpprefix'] > $int['gmaxprefixes'] ) {
                $int['maxprefixes'] = $int['maxbgpprefix'];
            } else {
                $int['maxprefixes'] = $int['gmaxprefixes'];
            }

            if( !$int['maxprefixes'] ) {
                $int['maxprefixes'] = 250;
            }

            unset( $int['gmaxprefixes'] );
            unset( $int['maxbgpprefix'] );

            if( $protocol == 6 && $int['peeringmacrov6'] ) {
                $int['peeringmacro'] = $int['peeringmacrov6'];
            }

            if( !$int['peeringmacro'] ) {
                $int['peeringmacro'] = 'AS' . $int['autsys'];
            }

            unset( $int['peeringmacrov6'] );

            if( !$int['bgpmd5secret'] ) {
                $int['bgpmd5secret'] = false;
            }

            if( $int['irrdbfilter'] ) {
                $int['irrdbfilter_prefixes'] = d2r( 'IrrdbPrefix' )->getForCustomerAndProtocol( $int[ 'cid' ], $protocol, true );
                $int['irrdbfilter_asns'    ] = d2r( 'IrrdbAsn'    )->getForCustomerAndProtocol( $int[ 'cid' ], $protocol, true );
            }

            $newints[ $int['address'] ] = $int;
        }

        return $newints;
    }


    /**
     * Provide array of vlan interfaces for the list Action
     *
     * @param int $id The VlanInterface to find
     * @return array array of vlan interfaces
     */
    public function getForList( int $id = null )
    {
        $dql = "SELECT vli.id AS id, vli.mcastenabled AS mcastenabled,
                 vli.ipv4enabled AS ipv4enabled, vli.ipv4hostname AS ipv4hostname, vli.ipv4canping AS ipv4canping,
                     vli.ipv4monitorrcbgp AS ipv4monitorrcbgp, vli.ipv4bgpmd5secret AS ipv4bgpmd5secret,
                 vli.ipv6enabled AS ipv6enabled, vli.ipv6hostname AS ipv6hostname, vli.ipv6canping AS ipv6canping,
                     vli.ipv6monitorrcbgp AS ipv6monitorrcbgp, vli.ipv6bgpmd5secret AS ipv6bgpmd5secret,
                 vli.irrdbfilter AS irrdbfilter, vli.bgpmd5secret AS bgpmd5secret, vli.maxbgpprefix AS maxbgpprefix,
                 vli.as112client AS as112client, vli.busyhost AS busyhost, vli.notes AS notes,
                 vli.rsclient AS rsclient,
                 ip4.address AS ipv4, ip6.address AS ipv6,
                 v.id AS vlanid, v.name AS vlan,
                 vi.id AS vintid,
                 c.name AS customer, c.id AS custid
                    FROM \\Entities\\VlanInterface vli
                        LEFT JOIN vli.VirtualInterface vi
                        LEFT JOIN vli.Vlan v
                        LEFT JOIN vli.IPv4Address ip4
                        LEFT JOIN vli.IPv6Address ip6
                        LEFT JOIN vi.Customer c";

        if( $id ){
            $dql .= " WHERE vli.id = $id ";
        }


        $q = $this->getEntityManager()->createQuery( $dql );
        return $q->getArrayResult();
    }

    /**
     * Get vli id / vi id / vlan tag / cust name matrix for sflow data processing
     *
     * Returns an array as follows:
     *
     *     [
     *         [ 'vliid' => 1, 'viid' => 2, 'tag' => 10, 'cname' => "OGCIO" ],
     *         ...
     *     ]
     *
     * @return array
     */
    public function sflowMatrixArray(): array
    {
        return $this->getEntityManager()->createQuery(
            "SELECT DISTINCT vli.id AS vliid, vi.id AS viid, v.number AS tag, c.name AS cname
                    FROM Entities\VlanInterface vli
                        LEFT JOIN vli.Vlan v
                        LEFT JOIN vli.VirtualInterface vi
                        LEFT JOIN vi.Customer c"
        )->getArrayResult();
    }

    /**
     * Get vi id / mac address for sflow data processing
     *
     * Returns an array as follows:
     *
     *     [
     *         [ 'viid' => 2, 'mac' => '112233445566' ],
     *         ...
     *     ]
     *
     * @return array
     */
    public function sflowMacTableArray(): array
    {
        return $this->getEntityManager()->createQuery(
            "SELECT DISTINCT vi.id AS viid, l2a.mac AS mac
                    FROM Entities\VlanInterface vli
                        LEFT JOIN vli.VirtualInterface vi
                        LEFT JOIN vli.layer2Addresses l2a
                    WHERE l2a.mac IS NOT NULL
                    ORDER BY viid"
        )->getArrayResult();
    }



    public function copyLayer2Addresses( VlanInterfaceEntity $s, VlanInterfaceEntity $d ): VlanInterface {
        foreach( $s->getLayer2Addresses() as $l2a ) {
            $n = new Layer2AddressEntity();
            $n->setVlanInterface( $d );
            $d->addLayer2Address( $n );
            $n->setMac( $l2a->getMac() );
            $this->getEntityManager()->persist( $n );
        }

        return $this;
    }

}
