delete from tt_content where pid in (
    select uid from pages where deleted = 1 or hidden = 1
);

delete from pages where deleted = 1 or hidden = 1;
