begin;

CREATE OR REPLACE FUNCTION public.quotation_report_fill_detailed(p_fromdate timestamp without time zone, p_todate timestamp without time zone, p_condition text, p_ledgerid character varying, p_branchid integer, p_salesmanid character varying, p_currencyid integer, p_userid integer, p_costcentreid character varying, p_partyname text, p_mode character varying DEFAULT 'detailed'::character varying)
 RETURNS TABLE("quotationMasterId" bigint, "SlNo" bigint, "Date" character varying, "QuotationNo" character varying, "ledgerCode" character varying, "ledgerName" character varying, "customerName" character varying, "TotalAmount" numeric, "BillDiscount" numeric, "OtherCharge" numeric, "TaxableAmt" numeric, "TotalTax" numeric, "RoundOff" numeric, "GrandAmount" numeric, "Narration" text, "ReferanceNo" character varying, "ReferanceDate" character varying, "CostCentre" character varying, "Approved" boolean, "Salesman" character varying, "DoneBy" character varying, "ForPackageSale" boolean, "CurrencySymbol" character varying, "AdditionalCost" numeric, barcode character varying, "productCode" character varying, "partNo" character varying, "productName" character varying, "productDescription" text, qty numeric, "unitName" character varying, rate numeric, "grossAmount" numeric, "discountPercentage" numeric, "netAmount" numeric, "taxName" character varying, "taxAmount" numeric, amount numeric)
 LANGUAGE plpgsql
AS $function$
DECLARE
    v_noOfDecimalPlace int := 2;
BEGIN
    SELECT COALESCE("noOfDecimalPlace",2)
    INTO v_noOfDecimalPlace
    FROM public."tbl_Currency"
    WHERE "currencyId" = p_currencyId;

    -- SUMMARY MODE
    IF p_mode = 'summary' THEN
        RETURN QUERY
        SELECT
            A."quotationMasterId",
            ROW_NUMBER() OVER (ORDER BY A."date", A."quotationMasterId")::bigint,
            to_char(A."date", 'DD-Mon-YYYY')::varchar,
            A."quotationNo",
            B."ledgerCode",
            B."ledgerName",
            A."customerName",
            ROUND(COALESCE(A."totalAmount",0) / NULLIF(A."exchangeRate",1), v_noOfDecimalPlace),
            ROUND(COALESCE(A."billDiscount",0) / NULLIF(A."exchangeRate",1), v_noOfDecimalPlace),
            ROUND(COALESCE(A."othercharge",0) / NULLIF(A."exchangeRate",1), v_noOfDecimalPlace),
            ROUND(COALESCE(A."taxableAmt",0) / NULLIF(A."exchangeRate",1), v_noOfDecimalPlace),
            ROUND(COALESCE(A."totalTax",0) / NULLIF(A."exchangeRate",1), v_noOfDecimalPlace),
            ROUND(COALESCE(A."roundOff",0) / NULLIF(A."exchangeRate",1), v_noOfDecimalPlace),
            ROUND(COALESCE(A."grandTotal",0) / NULLIF(A."exchangeRate",1), v_noOfDecimalPlace),
            A."narration",
            A."partyRefNo",
            to_char(A."partyRefDate", 'DD-Mon-YYYY')::varchar,
            CC."CostCentre",
            A."approved",
            F."employeeName",
            E."userName",
            false,
            CR."currencySymbol",
            A."additionalCost",
            NULL::varchar,
            NULL::varchar,
            NULL::varchar,
            NULL::varchar,
            NULL::text,
            NULL::numeric,
            NULL::varchar,
            NULL::numeric,
            NULL::numeric,
            NULL::numeric,
            NULL::numeric,
            NULL::varchar,
            NULL::numeric,
            NULL::numeric

        FROM public."tbl_QuotationMaster" A
        JOIN public."tbl_AccountLedger" B ON A."ledgerId" = B."ledgerId"
        LEFT JOIN public."tbl_User" E ON A."CreatedUser" = E."userName"
        LEFT JOIN public."tbl_Employee" F ON A."employeeId" = F."employeeId"
        JOIN public."tbl_CostCentre" CC ON A."costCentreId" = CC."costCentreId"
        JOIN public."tbl_CurrencyConversion" CU ON A."currencyConversionId" = CU."currencyConversionId"
        JOIN public."tbl_Currency" CR ON CU."currencyId" = CR."currencyId"

        WHERE
            A."branchId" = p_branchId
            AND A."date" BETWEEN p_fromDate AND p_toDate
            AND (p_ledgerId = 'All' OR A."ledgerId"::varchar = p_ledgerId)
            AND (p_salesManId IS NULL OR p_salesManId = '' OR A."employeeId" IS NULL OR A."employeeId"::varchar = p_salesManId)
            AND (p_condition IS NULL OR (p_condition = 'Approved' AND A."approved" = true) OR (p_condition = 'Pending' AND A."approved" = false))
            AND (p_costCentreId = 'All' OR A."costCentreId"::varchar = p_costCentreId)
            AND (p_partyName = 'All' OR A."customerName" = p_partyName)

        ORDER BY A."date", A."quotationMasterId";

    -- DETAILED MODE (DEFAULT)
    ELSE
        RETURN QUERY
        SELECT
            A."quotationMasterId",
            ROW_NUMBER() OVER (ORDER BY A."date", A."quotationMasterId")::bigint,
            to_char(A."date", 'DD-Mon-YYYY')::varchar,
            A."quotationNo",
            B."ledgerCode",
            B."ledgerName",
            A."customerName",
            ROUND(COALESCE(A."totalAmount",0) / NULLIF(A."exchangeRate",1), v_noOfDecimalPlace),
            ROUND(COALESCE(A."billDiscount",0) / NULLIF(A."exchangeRate",1), v_noOfDecimalPlace),
            ROUND(COALESCE(A."othercharge",0) / NULLIF(A."exchangeRate",1), v_noOfDecimalPlace),
            ROUND(COALESCE(A."taxableAmt",0) / NULLIF(A."exchangeRate",1), v_noOfDecimalPlace),
            ROUND(COALESCE(A."totalTax",0) / NULLIF(A."exchangeRate",1), v_noOfDecimalPlace),
            ROUND(COALESCE(A."roundOff",0) / NULLIF(A."exchangeRate",1), v_noOfDecimalPlace),
            ROUND(COALESCE(A."grandTotal",0) / NULLIF(A."exchangeRate",1), v_noOfDecimalPlace),
            A."narration",
            A."partyRefNo",
            to_char(A."partyRefDate", 'DD-Mon-YYYY')::varchar,
            CC."CostCentre",
            A."approved",
            F."employeeName",
            E."userName",
            false,
            CR."currencySymbol",
            A."additionalCost",
            D."barcode",
            D."productCode",
            COALESCE(P."partNo",''),
            P."productName",
            D."productDescription"::text,
            D."qty",
            UN."UnitName",
            D."rate",
            D."grossAmount",
            D."discountPercentage",
            D."netAmount",
            TM."taxName",
            D."taxAmount",
            D."amount"

        FROM public."tbl_QuotationMaster" A
        JOIN public."tbl_AccountLedger" B ON A."ledgerId" = B."ledgerId"
        JOIN public."tbl_QuotationDetails" D ON A."quotationMasterId" = D."quotationMasterId"
        LEFT JOIN public."tbl_User" E ON A."CreatedUser" = E."userName"
        LEFT JOIN public."tbl_Employee" F ON A."employeeId" = F."employeeId"
        LEFT JOIN public."tbl_CostCentre" CC ON A."costCentreId" = CC."costCentreId"
        LEFT JOIN public."tbl_Unit" UN ON D."unitId" = UN."unitId"
        LEFT JOIN public."tbl_Product" P ON D."productCode" = P."productCode"
        LEFT JOIN public."tbl_CurrencyConversion" CU ON A."currencyConversionId" = CU."currencyConversionId"
        LEFT JOIN public."tbl_Currency" CR ON CU."currencyId" = CR."currencyId"
        LEFT JOIN public."tbl_TaxMaster" TM ON D."taxId" = TM."taxId"

        WHERE
            A."branchId" = p_branchId
            AND A."date" BETWEEN p_fromDate AND p_toDate
            AND (p_ledgerId = 'All' OR A."ledgerId"::varchar = p_ledgerId)
            AND (p_salesManId IS NULL OR p_salesManId = '' OR A."employeeId" IS NULL OR A."employeeId"::varchar = p_salesManId)
            AND (p_condition IS NULL OR (p_condition = 'Approved' AND A."approved" = true) OR (p_condition = 'Pending' AND A."approved" = false))
            AND (p_costCentreId = 'All' OR A."costCentreId"::varchar = p_costCentreId)
            AND (p_partyName = 'All' OR A."customerName" = p_partyName)

        ORDER BY A."date", A."quotationMasterId";
    END IF;

END;
$function$







DROP FUNCTION IF EXISTS public.quotation_report_fill_detailed CASCADE;
CREATE OR REPLACE FUNCTION public.quotation_report_fill_detailed(p_fromdate timestamp without time zone, p_todate timestamp without time zone, p_condition text, p_ledgerid character varying, p_branchid integer, p_salesmanid character varying, p_currencyid integer, p_userid integer, p_costcentreid character varying, p_partyname text, p_mode character varying DEFAULT 'detailed'::character varying)
 RETURNS TABLE("quotationMasterId" bigint, "SlNo" bigint, "Date" character varying, "QuotationNo" character varying, "ledgerCode" character varying, "ledgerName" character varying, "customerName" character varying, "TotalAmount" numeric, "BillDiscount" numeric, "OtherCharge" numeric, "TaxableAmt" numeric, "TotalTax" numeric, "RoundOff" numeric, "GrandAmount" numeric, "Narration" text, "ReferanceNo" character varying, "ReferanceDate" character varying, "CostCentre" character varying, "Approved" boolean, "Salesman" character varying, "DoneBy" character varying, "ForPackageSale" boolean, "CurrencySymbol" character varying, "AdditionalCost" numeric, barcode character varying, "productCode" character varying, "partNo" character varying, "productName" character varying, "productDescription" text, qty numeric, "unitName" character varying, rate numeric, "grossAmount" numeric, "discountPercentage" numeric, "netAmount" numeric, "taxName" character varying, "taxAmount" numeric, amount numeric, "BillDiscOnProduct" numeric, "OtherChargeOnProduct" numeric)
 LANGUAGE plpgsql
AS $function$
DECLARE
    v_noOfDecimalPlace int := 2;
BEGIN
    SELECT COALESCE("noOfDecimalPlace",2)
    INTO v_noOfDecimalPlace
    FROM public."tbl_Currency"
    WHERE "currencyId" = p_currencyId;

    -- SUMMARY MODE
    IF p_mode = 'summary' THEN
        RETURN QUERY
        SELECT
            A."quotationMasterId",
            ROW_NUMBER() OVER (ORDER BY A."date", A."quotationMasterId")::bigint,
            to_char(A."date", 'DD-Mon-YYYY')::varchar,
            A."quotationNo",
            B."ledgerCode",
            B."ledgerName",
            A."customerName",
            ROUND(COALESCE(A."totalAmount",0) / NULLIF(A."exchangeRate",1), v_noOfDecimalPlace),
            ROUND(COALESCE(D."billDiscOnProduct",0) / NULLIF(A."exchangeRate",1), v_noOfDecimalPlace),
            ROUND(COALESCE(D."otherchargeonproduct",0) / NULLIF(A."exchangeRate",1), v_noOfDecimalPlace),
            ROUND(COALESCE(A."taxableAmt",0) / NULLIF(A."exchangeRate",1), v_noOfDecimalPlace),
            ROUND(COALESCE(A."totalTax",0) / NULLIF(A."exchangeRate",1), v_noOfDecimalPlace),
            ROUND(COALESCE(A."roundOff",0) / NULLIF(A."exchangeRate",1), v_noOfDecimalPlace),
            ROUND(COALESCE(A."grandTotal",0) / NULLIF(A."exchangeRate",1), v_noOfDecimalPlace),
            A."narration",
            A."partyRefNo",
            to_char(A."partyRefDate", 'DD-Mon-YYYY')::varchar,
            CC."CostCentre",
            A."approved",
            F."employeeName",
            E."userName",
            false,
            CR."currencySymbol",
            A."additionalCost",
            NULL::varchar,
            NULL::varchar,
            NULL::varchar,
            NULL::varchar,
            NULL::text,
            NULL::numeric,
            NULL::varchar,
            NULL::numeric,
            NULL::numeric,
            NULL::numeric,
            NULL::numeric,
            NULL::varchar,
            NULL::numeric,
            NULL::numeric,
            NULL::numeric,
            NULL::numeric

        FROM public."tbl_QuotationMaster" A
        JOIN public."tbl_AccountLedger" B ON A."ledgerId" = B."ledgerId"
        LEFT JOIN public."tbl_User" E ON A."CreatedUser" = E."userName"
        LEFT JOIN public."tbl_Employee" F ON A."employeeId" = F."employeeId"
        JOIN public."tbl_CostCentre" CC ON A."costCentreId" = CC."costCentreId"
        JOIN public."tbl_CurrencyConversion" CU ON A."currencyConversionId" = CU."currencyConversionId"
        JOIN public."tbl_Currency" CR ON CU."currencyId" = CR."currencyId"

        WHERE
            A."branchId" = p_branchId
            AND A."date" BETWEEN p_fromDate AND p_toDate
            AND (p_ledgerId = 'All' OR A."ledgerId"::varchar = p_ledgerId)
            AND (p_salesManId IS NULL OR p_salesManId = '' OR A."employeeId" IS NULL OR A."employeeId"::varchar = p_salesManId)
            AND (p_condition IS NULL OR (p_condition = 'Approved' AND A."approved" = true) OR (p_condition = 'Pending' AND A."approved" = false))
            AND (p_costCentreId = 'All' OR A."costCentreId"::varchar = p_costCentreId)
            AND (p_partyName = 'All' OR A."customerName" = p_partyName)

        ORDER BY A."date", A."quotationMasterId";

    -- DETAILED MODE (DEFAULT)
    ELSE
        RETURN QUERY
        SELECT
            A."quotationMasterId",
            ROW_NUMBER() OVER (ORDER BY A."date", A."quotationMasterId")::bigint,
            to_char(A."date", 'DD-Mon-YYYY')::varchar,
            A."quotationNo",
            B."ledgerCode",
            B."ledgerName",
            A."customerName",
            ROUND(COALESCE(A."totalAmount",0) / NULLIF(A."exchangeRate",1), v_noOfDecimalPlace),
            ROUND(COALESCE(D."billDiscOnProduct",0) / NULLIF(A."exchangeRate",1), v_noOfDecimalPlace),
            ROUND(COALESCE(D."otherchargeonproduct",0) / NULLIF(A."exchangeRate",1), v_noOfDecimalPlace),
            ROUND(COALESCE(A."taxableAmt",0) / NULLIF(A."exchangeRate",1), v_noOfDecimalPlace),
            ROUND(COALESCE(A."totalTax",0) / NULLIF(A."exchangeRate",1), v_noOfDecimalPlace),
            ROUND(COALESCE(A."roundOff",0) / NULLIF(A."exchangeRate",1), v_noOfDecimalPlace),
            ROUND(COALESCE(A."grandTotal",0) / NULLIF(A."exchangeRate",1), v_noOfDecimalPlace),
            A."narration",
            A."partyRefNo",
            to_char(A."partyRefDate", 'DD-Mon-YYYY')::varchar,
            CC."CostCentre",
            A."approved",
            F."employeeName",
            E."userName",
            false,
            CR."currencySymbol",
            A."additionalCost",
            D."barcode",
            D."productCode",
            COALESCE(P."partNo",''),
            P."productName",
            D."productDescription"::text,
            D."qty",
            UN."UnitName",
            D."rate",
            D."grossAmount",
            D."discountPercentage",
            D."netAmount",
            TM."taxName",
            D."taxAmount",
            D."amount",
            D."billDiscOnProduct"::numeric,
            D."otherchargeonproduct"::numeric

        FROM public."tbl_QuotationMaster" A
        JOIN public."tbl_AccountLedger" B ON A."ledgerId" = B."ledgerId"
        JOIN public."tbl_QuotationDetails" D ON A."quotationMasterId" = D."quotationMasterId"
        LEFT JOIN public."tbl_User" E ON A."CreatedUser" = E."userName"
        LEFT JOIN public."tbl_Employee" F ON A."employeeId" = F."employeeId"
        LEFT JOIN public."tbl_CostCentre" CC ON A."costCentreId" = CC."costCentreId"
        LEFT JOIN public."tbl_Unit" UN ON D."unitId" = UN."unitId"
        LEFT JOIN public."tbl_Product" P ON D."productCode" = P."productCode"
        LEFT JOIN public."tbl_CurrencyConversion" CU ON A."currencyConversionId" = CU."currencyConversionId"
        LEFT JOIN public."tbl_Currency" CR ON CU."currencyId" = CR."currencyId"
        LEFT JOIN public."tbl_TaxMaster" TM ON D."taxId" = TM."taxId"

        WHERE
            A."branchId" = p_branchId
            AND A."date" BETWEEN p_fromDate AND p_toDate
            AND (p_ledgerId = 'All' OR A."ledgerId"::varchar = p_ledgerId)
            AND (p_salesManId IS NULL OR p_salesManId = '' OR A."employeeId" IS NULL OR A."employeeId"::varchar = p_salesManId)
            AND (p_condition IS NULL OR (p_condition = 'Approved' AND A."approved" = true) OR (p_condition = 'Pending' AND A."approved" = false))
            AND (p_costCentreId = 'All' OR A."costCentreId"::varchar = p_costCentreId)
            AND (p_partyName = 'All' OR A."customerName" = p_partyName)

        ORDER BY A."date", A."quotationMasterId";
    END IF;

END;
$function$;
DROP FUNCTION IF EXISTS public.damage_stock_report_fill CASCADE;

CREATE OR REPLACE FUNCTION public.damage_stock_report_fill(fromdate timestamp without time zone, todate timestamp without time zone, branchid integer, userid character varying, p_mode character varying)
 RETURNS TABLE("damageStockMasterId" bigint, "SlNo" bigint, "VoucherNo" character varying, "Date" text, "TotalAmount" numeric, "Narration" text, "ProductCode" character varying, "Qty" numeric, "Rate" numeric, "UnitId" integer, "UnitName" character varying, "DetailAmount" numeric, "Barcode" text, "ProductDescription" text, "GodownId" integer, "RackId" character varying)
 LANGUAGE plpgsql
AS $function$
DECLARE
    v_decimalpoint INTEGER := 2;
BEGIN
    /* Get decimal places from branch currency */
    SELECT COALESCE(C."noOfDecimalPlace", 2)
    INTO v_decimalpoint
    FROM "tbl_Branch" B
    JOIN "tbl_Currency" C
        ON B."currencyId" = C."currencyId"
    WHERE B."branchId" = branchid;

    /* ==========================================================
       DETAILED MODE
       ========================================================== */
    IF LOWER(p_mode) = 'detailed' THEN

        RETURN QUERY
        SELECT
            A."damageStockMasterId"::bigint,
            ROW_NUMBER() OVER (
                ORDER BY A."date" DESC, A."damageStockMasterId" DESC
            )::bigint                                        AS "SlNo",
            A."voucherNo"::character varying                AS "VoucherNo",
            TO_CHAR(A."date", 'DD-Mon-YYYY')::text          AS "Date",
            ROUND(
                COALESCE(A."totalAmount", 0),
                v_decimalpoint
            )::numeric                                      AS "TotalAmount",
            A."narration"::text                             AS "Narration",
            -- Detail columns
            D."productCode"::character varying              AS "ProductCode",
            COALESCE(D."qty", 0)                            AS "Qty",
            COALESCE(D."rate", 0)                           AS "Rate",
            D."unitId"                                      AS "UnitId",
            U."UnitName"::character varying                 AS "UnitName",
            ROUND(
                COALESCE(D."amount", 0),
                v_decimalpoint
            )::numeric                                      AS "DetailAmount",
            D."barcode"                                     AS "Barcode",
            D."productDescription"                          AS "ProductDescription",
            D."GodownId"                                    AS "GodownId",
            D."RackId"::character varying                   AS "RackId"

        FROM "tbl_DamageStockMaster" A

        LEFT JOIN "tbl_DamageStockDetails1" D
            ON D."damageStockMasterId" = A."damageStockMasterId"

        LEFT JOIN "tbl_Unit" U
            ON U."unitId" = D."unitId"

        WHERE
            A."branchId" = branchid
            AND A."date" >= fromdate
            AND A."date" <  todate + INTERVAL '1 day'
            AND (
                userid IS NULL
                OR userid = ''
                OR A."CreatedUser" = userid
            )
        ORDER BY A."date" DESC, A."damageStockMasterId" DESC;

    /* ==========================================================
       SUMMARY MODE
       ========================================================== */
    ELSE

        RETURN QUERY
        SELECT
            A."damageStockMasterId"::bigint,
            ROW_NUMBER() OVER (
                ORDER BY A."date" DESC, A."damageStockMasterId" DESC
            )::bigint                                        AS "SlNo",
            A."voucherNo"::character varying                AS "VoucherNo",
            TO_CHAR(A."date", 'DD-Mon-YYYY')::text          AS "Date",
            ROUND(
                COALESCE(A."totalAmount", 0),
                v_decimalpoint
            )::numeric                                      AS "TotalAmount",
            A."narration"::text                             AS "Narration",
            -- NULL detail columns for summary
            NULL::character varying                         AS "ProductCode",
            NULL::numeric                                   AS "Qty",
            NULL::numeric                                   AS "Rate",
            NULL::integer                                   AS "UnitId",
            NULL::character varying                         AS "UnitName",
            NULL::numeric                                   AS "DetailAmount",
            NULL::text                                      AS "Barcode",
            NULL::text                                      AS "ProductDescription",
            NULL::integer                                   AS "GodownId",
            NULL::character varying                         AS "RackId"

        FROM "tbl_DamageStockMaster" A

        WHERE
            A."branchId" = branchid
            AND A."date" >= fromdate
            AND A."date" <  todate + INTERVAL '1 day'
            AND (
                userid IS NULL
                OR userid = ''
                OR A."CreatedUser" = userid
            )
        ORDER BY A."date" DESC, A."damageStockMasterId" DESC;

    END IF;

END;
$function$;



DROP FUNCTION IF EXISTS public.used_stock_report_fill CASCADE;

CREATE OR REPLACE FUNCTION public.used_stock_report_fill(fromdate timestamp without time zone, todate timestamp without time zone, branchid integer, userid character varying, p_mode character varying)
 RETURNS TABLE("usedStockMasterId" bigint, "SlNo" bigint, "VoucherNo" character varying, "Date" text, "TotalAmount" numeric, "Narration" text, "ProductCode" character varying, "Qty" numeric, "Rate" numeric, "UnitId" integer, "UnitName" character varying, "DetailAmount" numeric, "Barcode" text, "GodownId" integer, "RackId" character varying)
 LANGUAGE plpgsql
AS $function$
DECLARE
    v_decimalpoint INTEGER := 2;
BEGIN
    /* Get decimal places from branch currency */
    SELECT COALESCE(C."noOfDecimalPlace", 2)
    INTO v_decimalpoint
    FROM "tbl_Branch" B
    JOIN "tbl_Currency" C
        ON B."currencyId" = C."currencyId"
    WHERE B."branchId" = branchid;

    /* ==========================================================
       DETAILED MODE
       ========================================================== */
    IF LOWER(p_mode) = 'detailed' THEN

        RETURN QUERY
        SELECT
            A."usedStockMasterId"::bigint,
            ROW_NUMBER() OVER (
                ORDER BY A."date" DESC, A."usedStockMasterId" DESC
            )::bigint                                        AS "SlNo",
            A."voucherNo"::character varying                AS "VoucherNo",
            TO_CHAR(A."date", 'DD-Mon-YYYY')::text          AS "Date",
            ROUND(
                COALESCE(A."totalAmount", 0),
                v_decimalpoint
            )::numeric                                      AS "TotalAmount",
            A."narration"::text                             AS "Narration",
            -- Detail columns
            D."productCode"::character varying              AS "ProductCode",
            COALESCE(D."qty", 0)                            AS "Qty",
            COALESCE(D."rate", 0)                           AS "Rate",
            D."unitId"                                      AS "UnitId",
            U."UnitName"::character varying                 AS "UnitName",
            ROUND(
                COALESCE(D."amount", 0),
                v_decimalpoint
            )::numeric                                      AS "DetailAmount",
            D."barcode"                                     AS "Barcode",
            D."GodownId"                                    AS "GodownId",
            D."RackId"::character varying                   AS "RackId"

        FROM "tbl_UsedStockMaster" A

        LEFT JOIN "tbl_UsedStockDetails" D
            ON D."usedStockMasterId" = A."usedStockMasterId"

        LEFT JOIN "tbl_Unit" U
            ON U."unitId" = D."unitId"

        WHERE
            A."branchId" = branchid
            AND A."date" >= fromdate
            AND A."date" <  todate + INTERVAL '1 day'
            AND (
                userid IS NULL
                OR userid = ''
                OR A."CreatedUser" = userid
            )
        ORDER BY A."date" DESC, A."usedStockMasterId" DESC;

    /* ==========================================================
       SUMMARY MODE
       ========================================================== */
    ELSE

        RETURN QUERY
        SELECT
            A."usedStockMasterId"::bigint,
            ROW_NUMBER() OVER (
                ORDER BY A."date" DESC, A."usedStockMasterId" DESC
            )::bigint                                        AS "SlNo",
            A."voucherNo"::character varying                AS "VoucherNo",
            TO_CHAR(A."date", 'DD-Mon-YYYY')::text          AS "Date",
            ROUND(
                COALESCE(A."totalAmount", 0),
                v_decimalpoint
            )::numeric                                      AS "TotalAmount",
            A."narration"::text                             AS "Narration",
            -- NULL detail columns for summary
            NULL::character varying                         AS "ProductCode",
            NULL::numeric                                   AS "Qty",
            NULL::numeric                                   AS "Rate",
            NULL::integer                                   AS "UnitId",
            NULL::character varying                         AS "UnitName",
            NULL::numeric                                   AS "DetailAmount",
            NULL::text                                      AS "Barcode",
            NULL::integer                                   AS "GodownId",
            NULL::character varying                         AS "RackId"

        FROM "tbl_UsedStockMaster" A

        WHERE
            A."branchId" = branchid
            AND A."date" >= fromdate
            AND A."date" <  todate + INTERVAL '1 day'
            AND (
                userid IS NULL
                OR userid = ''
                OR A."CreatedUser" = userid
            )
        ORDER BY A."date" DESC, A."usedStockMasterId" DESC;

    END IF;

END;
$function$;



commit;

